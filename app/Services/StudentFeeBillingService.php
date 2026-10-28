<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\RegistrationFee;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StudentFeeBillingService
{
    public function generateForStudent(Student $student, int $durationYears, int $installmentCount, ?string $promoCode = null): StudentFeeTransaction
    {
        $student->loadMissing('classRoom');

        if (! $student->classRoom) {
            throw ValidationException::withMessages(['student' => 'Kelas siswa belum ditentukan sehingga tagihan bimbel belum dapat dibuat.']);
        }

        $class = $student->classRoom;
        $startGrade = (int) $class->grade_level;
        $maximumGrade = match ($class->education_level) {
            'SD' => 6,
            'SMP' => 9,
            'SMA' => 12,
            default => $startGrade,
        };
        $duration = min(max(1, $durationYears), 2, $maximumGrade - $startGrade + 1);
        $installmentCount = min(max(1, $installmentCount), 5);
        $fees = collect();
        $details = [];
        $total = 0;

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
                    'duration_years' => "Tarif Belajar untuk {$class->education_level} kelas {$grade} belum tersedia atau tidak aktif. Silakan hubungi Admin QSEE.",
                ]);
            }

            $fees->push($fee);
            $amount = (int) $fee->amount;
            $details[] = [
                'fee_id' => $fee->id,
                'name' => $fee->fee_type_label ?: $fee->name,
                'education_level' => $class->education_level,
                'grade_level' => $grade,
                'amount' => $amount,
                'discount_amount' => 0,
                'amount_due' => $amount,
            ];
            $total += $amount;
        }

        $maximumInstallments = (int) $fees->min('max_installments');
        if ($installmentCount > $maximumInstallments) {
            throw ValidationException::withMessages([
                'installment_count' => "Tarif yang dipilih hanya mengizinkan maksimal {$maximumInstallments} kali cicilan.",
            ]);
        }

        $promo = null;
        if (filled($promoCode)) {
            $promo = PromoCode::query()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($promoCode))])
                ->first();

            if (! $promo || ! $promo->isValidAt()) {
                throw ValidationException::withMessages(['promo_code' => 'Kode promo bimbel tidak valid, belum berlaku, atau sudah kedaluwarsa.']);
            }

            $eligiblePromoAmount = (int) $fees->filter(fn ($fee) => $fee->discount_enabled)->sum('amount');
            if ($eligiblePromoAmount <= 0) {
                throw ValidationException::withMessages(['promo_code' => 'Tarif Belajar yang dipilih tidak mengizinkan diskon/promo.']);
            }

            $remainingDiscount = $promo->discountFor($eligiblePromoAmount);
            foreach ($details as &$detail) {
                $fee = $fees->firstWhere('id', $detail['fee_id']);
                if (! $fee->discount_enabled || $remainingDiscount <= 0) {
                    continue;
                }

                $discount = min((int) $detail['amount'], $remainingDiscount);
                $detail['discount_amount'] = $discount;
                $detail['amount_due'] = (int) $detail['amount'] - $discount;
                $remainingDiscount -= $discount;
            }
            unset($detail);
            $total = array_sum(array_column($details, 'amount_due'));
        }

        $start = Carbon::today();
        $end = $start->copy()->addYears($duration)->subDay();

        $transaction = StudentFeeTransaction::create([
            'student_id' => $student->id,
            'registration_payment_id' => null,
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