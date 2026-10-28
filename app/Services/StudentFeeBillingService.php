<?php

namespace App\Services;

use App\Models\RegistrationFee;
use App\Models\RegistrationPayment;
use App\Models\PromoCode;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StudentFeeBillingService
{
    public function generateForRegistration(RegistrationPayment $payment): StudentFeeTransaction
    {
        $existing = StudentFeeTransaction::query()
            ->where('registration_payment_id', $payment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $student = $payment->student()->with('classRoom')->first();

        if (! $student || ! $student->classRoom) {
            throw ValidationException::withMessages([
                'student' => 'Kelas siswa belum ditentukan sehingga tagihan bimbel belum dapat dibuat.',
            ]);
        }

        $class = $student->classRoom;
        $startGrade = (int) $class->grade_level;
        $maximumGrade = match ($class->education_level) {
            'SD' => 6,
            'SMP' => 9,
            'SMA' => 12,
            default => $startGrade,
        };
        $duration = min(max(1, (int) $payment->duration_years), 2, $maximumGrade - $startGrade + 1);
        $details = [];
        $total = 0;
        $promo = null;

        if (filled($payment->tuition_promo_code)) {
            $promo = PromoCode::query()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($payment->tuition_promo_code))])
                ->first();

            if (! $promo || ! $promo->isValidAt()) {
                throw ValidationException::withMessages([
                    'tuition_promo_code' => 'Kode promo bimbel tidak valid atau sudah kedaluwarsa saat tagihan dibuat.',
                ]);
            }
        }

        for ($grade = $startGrade; $grade < $startGrade + $duration; $grade++) {
            $fee = RegistrationFee::query()
                ->active()
                ->where(function ($query) {
                    $query->where('fee_type', 'belajar')
                        ->orWhereRaw('LOWER(fee_type_label) = ?', ['belajar'])
                        ->orWhereRaw('LOWER(name) = ?', ['belajar']);
                })
                ->where('education_level', $class->education_level)
                ->where('grade_level', $grade)
                ->first();

            if (! $fee) {
                throw ValidationException::withMessages([
                    'registration_payment' => "Master Data Biaya untuk {$class->education_level} kelas {$grade} belum tersedia atau tidak aktif. Lengkapi tarif kelas tersebut sebelum menyetujui registrasi.",
                ]);
            }

            $amount = (int) $fee->amount;
            $discount = $promo && $fee->discount_enabled ? $promo->discountFor($amount) : 0;
            $amountDue = $amount - $discount;
            $details[] = [
                'fee_id' => $fee->id,
                'name' => $fee->fee_type_label ?: $fee->name,
                'education_level' => $class->education_level,
                'grade_level' => $grade,
                'amount' => $amount,
                'discount_amount' => $discount,
                'amount_due' => $amountDue,
            ];
            $total += $amountDue;
        }

        $installmentCount = min(max(1, (int) $payment->installment_count), 5);
        $maximumInstallments = min(array_map(function (array $detail) {
            return (int) RegistrationFee::query()->find($detail['fee_id'])->max_installments;
        }, $details));

        if ($installmentCount > $maximumInstallments) {
            throw ValidationException::withMessages([
                'installment_count' => "Jumlah cicilan melebihi batas pada Master Data Biaya. Maksimal yang diizinkan untuk tarif yang dipilih adalah {$maximumInstallments} kali.",
            ]);
        }

        $start = Carbon::today();
        $end = $start->copy()->addYears($duration)->subDay();

        $transaction = StudentFeeTransaction::create([
            'student_id' => $student->id,
            'registration_payment_id' => $payment->id,
            'duration_years' => $duration,
            'installment_count' => $installmentCount,
            'amount' => $total,
            'amount_paid' => 0,
            'details' => $details,
            'promo_code' => $promo?->code,
            'period_starts_at' => $start->toDateString(),
            'period_ends_at' => $end->toDateString(),
            'status' => 'awaiting_payment',
        ]);

        $base = intdiv($total, $installmentCount);
        $remainder = $total % $installmentCount;

        for ($number = 1; $number <= $installmentCount; $number++) {
            StudentFeePayment::create([
                'student_fee_transaction_id' => $transaction->id,
                'installment_number' => $number,
                'amount' => $base + ($number <= $remainder ? 1 : 0),
                'status' => 'awaiting_payment',
            ]);
        }

        return $transaction;
    }
}