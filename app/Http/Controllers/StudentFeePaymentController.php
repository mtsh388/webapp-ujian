<?php

namespace App\Http\Controllers;

use App\Models\StudentFeePayment;
use App\Models\StudentFeeTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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
            $query->where('student_id', $user->student->id);
        } elseif ($user->hasRole('Orang Tua')) {
            $studentIds = $user->parentProfile?->students()->pluck('students.id') ?? collect();
            $query->whereIn('student_id', $studentIds);
        } else {
            abort(403);
        }

        return view('student-fee-payments.index', [
            'transactions' => $query->paginate(15),
            'isParent' => $user->hasRole('Orang Tua'),
        ]);
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
                ...($isFirstApprovedPayment ? [
                    'period_starts_at' => now()->toDateString(),
                    'period_ends_at' => now()->addYears((int) $transaction->duration_years)->subDay()->toDateString(),
                ] : []),
            ]);

            if ($isFirstApprovedPayment) {
                $student->forceFill([
                    'is_active' => true,
                    'access_expires_at' => $transaction->fresh()->period_ends_at,
                ])->save();

                if ($student->user) {
                    $student->user->forceFill(['is_active' => true])->save();
                }
            }
        }, 3);

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