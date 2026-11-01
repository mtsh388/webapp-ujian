<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPayment;
use App\Models\User;
use App\Notifications\StudentClassPromotedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Notifications\StudentClassPromotedEmailNotification;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationPaymentController extends Controller
{
    public function index(): View
    {
        return view('registration-payments.index');
    }

    public function data(Request $request): JsonResponse
    {
        $recordsTotal = RegistrationPayment::query()->count();
        $query = RegistrationPayment::query()
            ->with(['user', 'student.classRoom', 'student.parents.user', 'verifier']);

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('status', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhereHas('classRoom', fn ($classQuery) => $classQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('parents', function ($parentQuery) use ($search) {
                                $parentQuery->where('full_name', 'like', "%{$search}%")
                                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
                            });
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();
        $columns = [0 => 'id', 1 => 'amount', 2 => 'status', 3 => 'created_at'];
        $orderColumn = $columns[(int) $request->input('order.0.column', 0)] ?? 'id';
        $direction = $request->input('order.0.dir', 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $payments = $query->orderBy($orderColumn, $direction)->skip($start)->take($length)->get();
        $data = $payments->map(function (RegistrationPayment $payment) {
            $student = '<div class="fw-semibold">' . e($payment->student?->full_name ?? $payment->user?->name ?? '-') . '</div>'
                . '<div class="small text-muted">' . e($payment->user?->email ?? '-') . '</div>'
                . '<div class="small text-muted">' . e($payment->student?->classRoom?->name ?? '-') . '</div>';
            $parents = $payment->student?->parents?->map(fn ($parent) => '<div>' . e($parent->full_name) . '</div><div class="small text-muted">' . e($parent->user?->email ?? '-') . '</div>')->implode('') ?: '-';
            $statusClass = $payment->status === 'verified' ? 'success' : ($payment->status === 'pending_verification' ? 'warning' : ($payment->status === 'rejected' ? 'danger' : 'secondary'));
            $status = '<span class="badge bg-label-' . $statusClass . '">' . e(str_replace('_', ' ', ucfirst($payment->status))) . '</span>';
            if ($payment->rejection_reason) {
                $status .= '<div class="small text-danger mt-1">' . e($payment->rejection_reason) . '</div>';
            }
            $proof = $payment->proof_path ? '<a class="btn btn-sm btn-outline-primary" href="' . e(route('registration-payments.proof', $payment)) . '">Lihat Bukti</a>' : '-';
            if ($payment->status === 'pending_verification') {
                $actions = '<div class="d-flex flex-column gap-2"><form method="POST" action="' . e(route('registration-payments.approve', $payment)) . '">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-success">Setujui</button></form>'
                    . '<form method="POST" action="' . e(route('registration-payments.reject', $payment)) . '">' . csrf_field() . '<input name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan" required maxlength="1000"><button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button></form></div>';
            } else {
                $actions = '<span class="text-muted small">' . e($payment->verifier?->name ?? '-') . '</span>';
            }

            return ['student' => $student, 'parents' => $parents, 'amount' => 'Rp ' . number_format((int) $payment->amount, 0, ',', '.'), 'status' => $status, 'proof' => $proof, 'actions' => $actions];
        });

        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $data]);
    }

    public function showPayment(RegistrationPayment $registrationPayment): View|RedirectResponse
    {
        if (! in_array($registrationPayment->status, ['awaiting_payment', 'rejected'], true)) {
            return redirect()->route('login')->with('status', 'Bukti pembayaran sudah dikirim dan sedang diproses.');
        }

        $registrationPayment->load(['user', 'student.classRoom']);

        return view('auth.registration-payment', ['payment' => $registrationPayment]);
    }

    public function submitProof(Request $request, RegistrationPayment $registrationPayment): RedirectResponse
    {
        if (! in_array($registrationPayment->status, ['awaiting_payment', 'rejected'], true)) {
            return redirect()->route('login')->with('status', 'Bukti pembayaran sudah dikirim dan sedang diproses.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ]);

        $previousProofPath = $registrationPayment->proof_path;
        $proofPath = $validated['payment_proof']->store('registration-proofs', 'local');

        try {
            DB::transaction(function () use ($registrationPayment, $proofPath) {
                $payment = RegistrationPayment::query()
                    ->whereKey($registrationPayment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($payment->status, ['awaiting_payment', 'rejected'], true)) {
                    throw new \RuntimeException('Bukti pembayaran sudah dikirim dan sedang diproses.');
                }

                $payment->update([
                    'proof_path' => $proofPath,
                    'status' => 'pending_verification',
                    'rejection_reason' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        if ($previousProofPath && $previousProofPath !== $proofPath) {
            Storage::disk('local')->delete($previousProofPath);
        }

        try {
            $notice = new StudentClassPromotedNotification(
                'Pembayaran registrasi menunggu verifikasi',
                'Bukti pembayaran registrasi ' . ($registrationPayment->user?->name ?? 'siswa') . ' telah diunggah dan menunggu verifikasi.',
                false,
                route('registration-payments.index', absolute: false)
            );
            User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->whereIn('name', ['Admin', 'Keuangan']))->get()->each(fn (User $recipient) => $recipient->notify($notice));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi. Akun Siswa dan Orang Tua dapat digunakan setelah pembayaran disetujui.');
    }

    public function proof(RegistrationPayment $registrationPayment)
    {
        abort_unless(filled($registrationPayment->proof_path) && Storage::disk('local')->exists($registrationPayment->proof_path), 404);

        return Storage::disk('local')->response($registrationPayment->proof_path);
    }

    public function approve(RegistrationPayment $registrationPayment): RedirectResponse
    {
        DB::transaction(function () use ($registrationPayment) {
            $payment = RegistrationPayment::query()
                ->whereKey($registrationPayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'pending_verification') {
                abort(422, 'Pembayaran ini tidak sedang menunggu verifikasi.');
            }

            $payment->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            if ($payment->student) {
                $payment->student->forceFill(['is_active' => false])->save();

                $payment->student->parents()->with('user')->get()->each(function ($parent) {
                    $parent->forceFill(['is_active' => true])->save();

                    if ($parent->user) {
                        $parent->user->forceFill(['is_active' => true])->save();
                    }
                });
            }
        }, 3);

        try {
            $payment = $registrationPayment->fresh(['user', 'student.parents.user']);
            $emails = collect([$payment?->user?->email])
                ->merge($payment?->student?->parents?->pluck('user.email') ?? collect())
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($emails as $email) {
                $recipient = User::query()->where('email', $email)->first();
                $recipient?->notify(new StudentClassPromotedEmailNotification(
                    'QSEE - Pembayaran Registrasi Disetujui',
                    'Pembayaran registrasi QSEE telah disetujui. Biaya bimbel tidak termasuk dalam transaksi registrasi. Silakan buka menu Pembayaran Bimbel untuk memilih periode dan membuat tagihan terpisah.'
                ));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $payment = $registrationPayment->fresh(['user', 'student.parents.user']);
            $notice = new StudentClassPromotedNotification('Pembayaran registrasi disetujui', 'Pembayaran registrasi QSEE telah disetujui. Silakan lanjutkan proses aktivasi dan pembayaran bimbel.', true, route('student-fee-payments.index', absolute: false));
            collect([$payment?->user])->merge($payment?->student?->parents?->pluck('user') ?? collect())->filter()->unique('id')->each(fn (User $recipient) => $recipient->notify($notice));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('registration-payments.index')->with('success', 'Pembayaran registrasi disetujui. Akun Orang Tua diaktifkan; silakan pilih periode dan buat tagihan melalui menu Pembayaran Bimbel.');
    }

    public function reject(Request $request, RegistrationPayment $registrationPayment): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($registrationPayment, $validated) {
            $payment = RegistrationPayment::query()
                ->whereKey($registrationPayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'pending_verification') {
                abort(422, 'Pembayaran ini tidak sedang menunggu verifikasi.');
            }

            $payment->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        }, 3);

        try {
            $payment = $registrationPayment->fresh(['user', 'student.parents.user']);
            $emails = collect([$payment?->user?->email])
                ->merge($payment?->student?->parents?->pluck('user.email') ?? collect())
                ->filter()
                ->unique()
                ->values()
                ->all();

            $paymentUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'register.payment.show',
                now()->addDays(30),
                ['registrationPayment' => $registrationPayment->id]
            );

            foreach ($emails as $email) {
                $recipient = User::query()->where('email', $email)->first();
                $recipient?->notify(new StudentClassPromotedEmailNotification(
                    'QSEE - Pembayaran Registrasi Perlu Diperbaiki',
                    "Pembayaran registrasi QSEE ditolak. Alasan: {$validated['rejection_reason']}\n\nSilakan buka tautan berikut untuk memperbaiki dan mengunggah ulang bukti pembayaran:\n{$paymentUrl}"
                ));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $payment = $registrationPayment->fresh(['user', 'student.parents.user']);
            $notice = new StudentClassPromotedNotification('Pembayaran registrasi ditolak', 'Pembayaran registrasi ditolak. Alasan: ' . $validated['rejection_reason'], true, route('my-registration-payments.index', absolute: false), 'Periksa pembayaran');
            collect([$payment?->user])->merge($payment?->student?->parents?->pluck('user') ?? collect())->filter()->unique('id')->each(fn (User $recipient) => $recipient->notify($notice));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('registration-payments.index')->with('success', 'Pembayaran ditolak. Alasan penolakan disimpan dan pengguna dapat mengunggah ulang bukti melalui tautan pembayaran yang masih berlaku.');
    }
}
