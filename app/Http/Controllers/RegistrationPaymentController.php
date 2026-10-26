<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
