<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPayment;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeTransaction;
use App\Models\User;
use App\Notifications\StudentClassPromotedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Services\StudentFeeBillingService;
use Throwable;

class StudentFeePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $search = trim((string) $request->query('search', ''));
        $query = StudentFeeTransaction::query()
            ->with(['student', 'installments.verifier'])
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('full_name', 'like', "%{$search}%");
                    })
                        ->orWhere('class_name_snapshot', 'like', "%{$search}%")
                    })
                        ->orWhere('package_type', 'like', "%{$search}%")
                        ->orWhereRaw('CAST(amount AS CHAR) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw("DATE_FORMAT(period_starts_at, '%d %b %Y') LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("DATE_FORMAT(period_ends_at, '%d %b %Y') LIKE ?", ["%{$search}%"]);
                });
            })
            ->latest();

        if ($user->hasRole('Siswa')) {
            abort_unless($user->student, 403);
            $studentIds = collect([$user->student->id]);
            $query->whereIn('student_id', $studentIds);
        } elseif ($user->hasRole('Orang Tua')) {
            $studentIds = $user->parentProfile?->students()->pluck('students.id') ?? collect();
            $query->whereIn('student_id', $studentIds);
        } else {
            abort(403);
        }

        $eligibleStudents = Student::query()
            ->with('classRoom')
            ->when(! $user->hasRole('Orang Tua'), fn ($builder) => $builder->whereRaw('1 = 0'))
            ->whereIn('id', $studentIds)
            ->whereIn('id', RegistrationPayment::query()->where('status', 'verified')->select('student_id'))
            ->whereHas('classRoom')
            ->where(function ($builder) {
                $builder->whereDoesntHave('classRoom', function ($classQuery) {
                    $classQuery->where('education_level', 'SMA')
                        ->where('grade_level', 12);
                })->orWhereDoesntHave('feeTransactions');
            })
            ->whereDoesntHave('feeTransactions', fn ($builder) => $builder->whereIn('status', ['awaiting_payment', 'pending_verification', 'partial']))
            ->get();

        return view('student-fee-payments.index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'eligibleStudents' => $eligibleStudents,
            'isParent' => $user->hasRole('Orang Tua'),
        ]);
    }

    public function createPlan(Request $request, StudentFeeBillingService $billingService): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'package_type' => ['required', 'string', 'in:semester,yearly'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:5'],
            'promo_code' => ['nullable', 'string', 'max:100'],
        ]);

        $user = auth()->user();
        $student = Student::query()->with('classRoom')->findOrFail($validated['student_id']);

        if ($user->hasRole('Orang Tua')) {
            $isLinked = $user->parentProfile?->students()->where('students.id', $student->id)->exists() ?? false;
            abort_unless($isLinked, 403);
        } else {
            abort(403);
        }

        abort_unless(
            RegistrationPayment::query()->where('student_id', $student->id)->where('status', 'verified')->exists(),
            422,
            'Pembayaran registrasi harus disetujui sebelum membuat tagihan bimbel.'
        );

        abort_unless(
            ! StudentFeeTransaction::query()->where('student_id', $student->id)->whereIn('status', ['awaiting_payment', 'pending_verification', 'partial'])->exists(),
            422,
            'Siswa masih memiliki tagihan bimbel yang belum lunas.'
        );

        $transaction = DB::transaction(function () use ($billingService, $student, $validated) {
            $lockedStudent = Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                RegistrationPayment::query()->where('student_id', $lockedStudent->id)->where('status', 'verified')->exists(),
                422,
                'Pembayaran registrasi harus disetujui sebelum memperpanjang paket bimbel.'
            );

            abort_unless(
                ! StudentFeeTransaction::query()
                    ->where('student_id', $lockedStudent->id)
                    ->whereIn('status', ['awaiting_payment', 'pending_verification', 'partial'])
                    ->exists(),
                422,
                'Siswa masih memiliki tagihan bimbel yang belum lunas. Selesaikan tagihan tersebut sebelum memperpanjang paket.'
            );

            $lockedStudent->loadMissing('classRoom');
            $isSma12 = $lockedStudent->classRoom?->education_level === 'SMA'
                && (int) $lockedStudent->classRoom?->grade_level === 12;

            abort_unless(
                ! $isSma12 || ! StudentFeeTransaction::query()->where('student_id', $lockedStudent->id)->exists(),
                422,
                'Siswa SMA kelas 12 hanya dapat berlangganan satu kali dan tidak dapat melakukan perpanjangan.'
            );

            return $billingService->generateForStudent(
                $lockedStudent->load('classRoom'),
                $validated['package_type'],
                (int) $validated['installment_count'],
                $validated['promo_code'] ?? null
            );
        }, 3);

        try {
            $notice = new StudentClassPromotedNotification(
                'Tagihan bimbel baru',
                'Tagihan bimbel untuk ' . $student->full_name . ' berhasil dibuat dan menunggu pembayaran.'
            );
            User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->whereIn('name', ['Admin', 'Keuangan']))->get()->each(fn (User $recipient) => $recipient->notify($notice));
            $student->loadMissing('user');
            $student->user?->notify($notice);
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('student-fee-payments.index')->with('success', 'Tagihan perpanjangan paket bimbel berhasil dibuat. Periode paket aktif tetap diperhitungkan.');
    }

    public function adminIndex(): View
    {
        return view('student-fee-payments.admin-index');
    }

    public function adminData(Request $request): JsonResponse
    {
        $baseQuery = StudentFeePayment::query()
            ->join('student_fee_transactions', 'student_fee_transactions.id', '=', 'student_fee_payments.student_fee_transaction_id')
            ->join('students', 'students.id', '=', 'student_fee_transactions.student_id')
            ;

        $recordsTotal = (clone $baseQuery)->count('student_fee_payments.id');
        $query = StudentFeePayment::query()
            ->select('student_fee_payments.*')
            ->join('student_fee_transactions', 'student_fee_transactions.id', '=', 'student_fee_payments.student_fee_transaction_id')
            ->join('students', 'students.id', '=', 'student_fee_transactions.student_id')
            ->with(['transaction.student', 'verifier']);

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('students.full_name', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.class_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.education_level_snapshot', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.grade_level_snapshot', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.package_type', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.period_starts_at', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.period_ends_at', 'like', "%{$search}%")
                    ->orWhere('student_fee_transactions.amount', 'like', "%{$search}%")
                    ->orWhere('student_fee_payments.status', 'like', "%{$search}%")
                    ->orWhere('student_fee_payments.rejection_reason', 'like', "%{$search}%")
                    ->orWhere('student_fee_payments.installment_number', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count('student_fee_payments.id');
        $columns = [0 => 'students.full_name', 1 => 'student_fee_transactions.package_type', 2 => 'student_fee_transactions.amount', 3 => 'student_fee_payments.installment_number', 4 => 'student_fee_payments.status'];
        $orderColumn = $columns[(int) $request->input('order.0.column', 0)] ?? 'student_fee_payments.created_at';
        $direction = $request->input('order.0.dir', 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $payments = $query->orderBy($orderColumn, $direction)->skip($start)->take($length)->get();
        $data = $payments->map(function (StudentFeePayment $payment) {
            $transaction = $payment->transaction;
            $student = $transaction?->student;
            $studentHtml = '<div class="fw-semibold">' . e($student?->full_name ?? '-') . '</div><div class="small text-muted">' . e($transaction?->class_name_snapshot ?: 'Kelas belum tercatat') . '</div>';
            $packageName = $transaction?->package_type === 'semester' ? '1 Semester' : '1 Tahun';
            $period = ($transaction?->period_starts_at?->format('d M Y') ?? '-') . '–' . ($transaction?->period_ends_at?->format('d M Y') ?? '-');
            $packageHtml = '<div class="fw-semibold">' . e($packageName) . '</div><div class="small text-muted">' . e($period) . '</div>';
            $amountHtml = '<div>Tagihan: Rp ' . number_format((int) ($transaction?->amount ?? 0), 0, ',', '.') . '</div><div class="small text-muted">Cicilan: Rp ' . number_format((int) $payment->amount, 0, ',', '.') . '</div>';
            $installmentHtml = $payment->installment_number . ' / ' . ($transaction?->installment_count ?? '-') . '<div class="small text-muted">Jatuh tempo: ' . e($payment->due_date?->format('d M Y') ?? '-') . '</div>';
            $statusClass = $payment->status === 'verified' ? 'success' : ($payment->status === 'pending_verification' ? 'warning' : ($payment->status === 'rejected' ? 'danger' : 'secondary'));
            $installmentHtml .= '<div><span class="badge bg-label-' . $statusClass . '">' . e(str_replace('_', ' ', ucfirst($payment->status))) . '</span></div>';
            if ($payment->rejection_reason) {
                $installmentHtml .= '<div class="small text-danger">' . e($payment->rejection_reason) . '</div>';
            }
            $proof = $payment->proof_path ? '<a class="btn btn-sm btn-outline-primary" href="' . e(route('student-fee-payments.proof', $payment)) . '">Lihat Bukti</a>' : '<span class="text-muted">Belum ada</span>';
            if ($payment->status === 'pending_verification') {
                $actions = '<div class="d-flex flex-column gap-2"><form method="POST" action="' . e(route('student-fee-payments.approve', $payment)) . '">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-success">Setujui</button></form>'
                    . '<form method="POST" action="' . e(route('student-fee-payments.reject', $payment)) . '">' . csrf_field() . '<input name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan" maxlength="1000" required><button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button></form></div>';
            } else {
                $actions = '<span class="text-muted small">' . e($payment->verifier?->name ?? '-') . '</span>';
            }

            return ['student' => $studentHtml, 'package' => $packageHtml, 'amount' => $amountHtml, 'installment' => $installmentHtml, 'proof' => $proof, 'actions' => $actions];
        });

        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $data]);
    }

    public function submitProof(Request $request, StudentFeePayment $studentFeePayment): RedirectResponse
    {
        $this->authorizeOwner($studentFeePayment);

        abort_unless(in_array($studentFeePayment->status, ['awaiting_payment', 'rejected'], true), 422, 'Cicilan ini tidak dapat menerima bukti pembayaran baru.');

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ]);

        $oldPath = $studentFeePayment->proof_path;
        $newPath = $validated['payment_proof']->store('student-fee-proofs', 'local');

        try {
            DB::transaction(function () use ($studentFeePayment, $newPath) {
                $payment = StudentFeePayment::query()->whereKey($studentFeePayment->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($payment->status, ['awaiting_payment', 'rejected'], true), 422, 'Cicilan ini tidak dapat menerima bukti pembayaran baru.');

                $payment->update([
                    'proof_path' => $newPath,
                    'status' => 'pending_verification',
                    'rejection_reason' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        $student = $studentFeePayment->transaction()->with('student.user', 'student.parents.user')->firstOrFail()->student;
        $notice = new StudentClassPromotedNotification('Bukti pembayaran bimbel masuk', 'Bukti pembayaran bimbel untuk ' . $student->full_name . ' menunggu verifikasi.');
        User::role(['Admin', 'Keuangan'])->each(fn (User $user) => $user->notify($notice));

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Bukti pembayaran bimbel berhasil dikirim dan menunggu verifikasi Keuangan.');
    }

    public function proof(StudentFeePayment $studentFeePayment)
    {
        abort_unless(filled($studentFeePayment->proof_path) && Storage::disk('local')->exists($studentFeePayment->proof_path), 404);

        return Storage::disk('local')->response($studentFeePayment->proof_path);
    }

    public function approve(StudentFeePayment $studentFeePayment): RedirectResponse
    {
        DB::transaction(function () use ($studentFeePayment) {
            $payment = StudentFeePayment::query()
                ->whereKey($studentFeePayment->id)
                ->lockForUpdate()
                ->with('transaction.student.user')
                ->firstOrFail();

            abort_unless($payment->status === 'pending_verification', 422, 'Cicilan ini tidak sedang menunggu verifikasi.');

            $transaction = StudentFeeTransaction::query()
                ->whereKey($payment->student_fee_transaction_id)
                ->lockForUpdate()
                ->firstOrFail();
            $student = $transaction->student()->with('user')->firstOrFail();
            $isFirstApprovedPayment = ! $transaction->installments()->where('status', 'verified')->exists();

            $payment->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $amountPaid = (int) $transaction->installments()->where('status', 'verified')->sum('amount');
            $transaction->update([
                'amount_paid' => $amountPaid,
                'status' => $amountPaid >= $transaction->amount ? 'paid' : 'partial',

            ]);

            if ($isFirstApprovedPayment) {
                $student->forceFill([
                    'is_active' => true,
                    'access_expires_at' => $transaction->period_ends_at,
                ])->save();

                if ($student->user) {
                    $student->user->forceFill(['is_active' => true])->save();
                }
            }
        }, 3);

        $student = $studentFeePayment->transaction()->with('student.user', 'student.parents.user')->firstOrFail()->student;
        $notice = new StudentClassPromotedNotification('Pembayaran bimbel disetujui', 'Pembayaran bimbel untuk ' . $student->full_name . ' telah disetujui.');
        $student->user?->notify($notice);
        $student->parents->each(fn ($parent) => $parent->user?->notify($notice));

        return redirect()->route('student-fee-payments.admin')->with('success', 'Pembayaran cicilan bimbel disetujui.');
    }

    public function reject(Request $request, StudentFeePayment $studentFeePayment): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($studentFeePayment, $validated) {
            $payment = StudentFeePayment::query()->whereKey($studentFeePayment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->status === 'pending_verification', 422, 'Cicilan ini tidak sedang menunggu verifikasi.');

            $payment->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        }, 3);

        $student = $studentFeePayment->transaction()->with('student.user', 'student.parents.user')->firstOrFail()->student;
        $notice = new StudentClassPromotedNotification('Pembayaran bimbel ditolak', 'Pembayaran bimbel untuk ' . $student->full_name . ' ditolak. Alasan: ' . $validated['rejection_reason']);
        $student->user?->notify($notice);
        $student->parents->each(fn ($parent) => $parent->user?->notify($notice));

        return redirect()->route('student-fee-payments.admin')->with('success', 'Pembayaran cicilan ditolak. Alasan penolakan disimpan.');
    }

    private function authorizeOwner(StudentFeePayment $payment): void
    {
        $user = auth()->user();
        $student = $payment->transaction()->with('student')->firstOrFail()->student;

        if ($user->hasRole('Siswa')) {
            abort_unless($student && $student->user_id === $user->id, 403);
            return;
        }

        if ($user->hasRole('Orang Tua')) {
            $isLinked = $user->parentProfile?->students()->where('students.id', $student?->id)->exists() ?? false;
            abort_unless($isLinked, 403);
            return;
        }

        abort(403);
    }
}