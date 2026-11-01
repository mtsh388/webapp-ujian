<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPayment;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeTransaction;
use App\Models\User;
use App\Notifications\StudentClassPromotedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Services\StudentFeeBillingService;
use Throwable;

class StudentFeePaymentController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $query = StudentFeeTransaction::query()
            ->with(['student.classRoom', 'installments.verifier'])
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
            ->whereDoesntHave('feeTransactions', fn ($builder) => $builder->whereIn('status', ['awaiting_payment', 'pending_verification', 'partial']))
            ->get();

        return view('student-fee-payments.index', [
            'transactions' => $query->paginate(15),
            'eligibleStudents' => $eligibleStudents,
            'isParent' => $user->hasRole('Orang Tua'),
        ]);
    }

    public function createPlan(Request $request, StudentFeeBillingService $billingService): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'package_type' => ['required', 'string', 'in:semester,yearly,two_years'],
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
            return $billingService->generateForStudent(
                $student,
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
            User::role(['Admin', 'Keuangan'])->where('is_active', true)->get()->each(fn (User $recipient) => $recipient->notify($notice));
            $student->loadMissing('user');
            $student->user?->notify($notice);
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('student-fee-payments.index')->with('success', 'Tagihan bimbel berhasil dibuat terpisah dari registrasi.');
    }

    public function adminIndex(): View
    {
        return view('student-fee-payments.admin-index', [
            'transactions' => StudentFeeTransaction::query()
                ->with(['student.classRoom', 'installments.verifier'])
                ->latest()
                ->paginate(20),
        ]);
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