<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationPaymentController extends Controller
{
    public function index(): View
    {
        return view('registration-payments.index', [
            'payments' => RegistrationPayment::query()
                ->with(['user', 'student.classRoom', 'student.parents.user', 'verifier'])
                ->latest()
                ->paginate(20),
        ]);
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
            $financeEmails = \App\Models\User::query()
                ->role('Keuangan')
                ->where('is_active', true)
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($financeEmails !== []) {
                Mail::raw(
                    'Ada pembayaran registrasi baru yang menunggu verifikasi. Silakan buka menu Keuangan > Verifikasi Pembayaran di QSEE.',
                    function ($message) use ($financeEmails) {
                        $message->to($financeEmails)->subject('QSEE - Pembayaran Registrasi Menunggu Verifikasi');
                    }
                );
            }
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

            $payment->user()->update(['is_active' => true]);

            if ($payment->student) {
                $student = $payment->student;
                $student->update(['is_active' => true]);

                $student->parents()->with('user')->get()->each(function ($parent) {
                    $parent->forceFill(['is_active' => true])->save();

                    if ($parent->user) {
                        $parent->user->forceFill(['is_active' => true])->save();
                    }
                });
            }
        }, 3);

        return redirect()->route('registration-payments.index')->with('success', 'Pembayaran disetujui. Akun Siswa dan Orang Tua serta status siswa telah diaktifkan.');
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

        return redirect()->route('registration-payments.index')->with('success', 'Pembayaran ditolak. Alasan penolakan disimpan dan pengguna dapat mengunggah ulang bukti melalui tautan pembayaran yang masih berlaku.');
    }
}
