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
        if ($registrationPayment->status !== 'awaiting_payment') {
            return redirect()->route('login')->with('status', 'Pembayaran registrasi sudah dikirim atau diproses.');
        }

        $registrationPayment->load(['user', 'student.classRoom']);

        return view('auth.registration-payment', ['payment' => $registrationPayment]);
    }

    public function submitProof(Request $request, RegistrationPayment $registrationPayment): RedirectResponse
    {
        if ($registrationPayment->status !== 'awaiting_payment') {
            return redirect()->route('login')->with('status', 'Pembayaran registrasi sudah dikirim atau diproses.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ]);

        $proofPath = $validated['payment_proof']->store('registration-proofs', 'local');

        try {
            DB::transaction(function () use ($registrationPayment, $proofPath) {
                $payment = RegistrationPayment::query()
                    ->whereKey($registrationPayment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($payment->status !== 'awaiting_payment') {
                    throw new \RuntimeException('Pembayaran registrasi sudah dikirim atau diproses.');
                }

                $payment->update([
                    'proof_path' => $proofPath,
                    'status' => 'pending_verification',
                ]);
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
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
            ->with('status', 'Selamat, Anda sudah bergabung. Silakan datang ke QSEE untuk melakukan proses selanjutnya. Akun dapat digunakan setelah pembayaran disetujui Keuangan.');
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
                $payment->student->update(['is_active' => true]);
                $payment->student->parents()->with('user')->get()->each(function ($parent) {
                    if ($parent->user) {
                        $parent->user->forceFill(['is_active' => true])->save();
                    }
                });
            }
        }, 3);

        return redirect()->route('registration-payments.index')->with('success', 'Pembayaran disetujui. Akun siswa dan orang tua diaktifkan.');
    }

    public function reject(Request $request, RegistrationPayment $registrationPayment): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment = RegistrationPayment::query()
            ->whereKey($registrationPayment->id)
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

        return redirect()->route('registration-payments.index')->with('success', 'Pembayaran ditolak. Siswa dapat menghubungi Keuangan untuk tindak lanjut.');
    }
}
