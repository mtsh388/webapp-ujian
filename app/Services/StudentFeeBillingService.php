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
    public function generateForStudent(Student $student, string $packageType, int $installmentCount, ?string $promoCode = null): StudentFeeTransaction
    {
        $student->loadMissing('classRoom');

        if (! $student->classRoom) {
            throw ValidationException::withMessages(['student' => 'Kelas siswa belum ditentukan sehingga tagihan bimbel belum dapat dibuat.']);
        }

        $package = match ($packageType) {
            'semester' => ['years' => 1, 'billing_period' => 'semester', 'label' => '1 Semester'],
            'yearly' => ['years' => 1, 'billing_period' => 'yearly', 'label' => '1 Tahun'],
            default => throw ValidationException::withMessages(['package_type' => 'Paket belajar tidak valid.']),
        };

        $class = $student->classRoom;
        $startGrade = (int) $class->grade_level;
        $today = Carbon::today();
        $activeUntil = $student->is_active && $student->access_expires_at && $student->access_expires_at->greaterThanOrEqualTo($today)
            ? $student->access_expires_at->copy()->startOfDay()
            : null;

        $start = $activeUntil ? $activeUntil->copy()->addDay() : $today->copy();

        if ($class->education_level === 'SMA') {
            $isGradeTwelve = $startGrade === 12;
            $isOddSemester = $start->month >= 7;

            if (($isGradeTwelve && $packageType !== 'yearly') || (! $isGradeTwelve && ! $isOddSemester && $packageType === 'yearly')) {
                throw ValidationException::withMessages([
                    'package_type' => $isGradeTwelve
                        ? 'Siswa kelas 12 hanya dapat memilih paket tahunan.'
                        : 'Pada semester genap, siswa kelas 10 dan 11 hanya dapat memilih paket semesteran.',
                ]);
            }
        }

        $end = $packageType === 'semester'
            ? ($start->month >= 7
                ? $start->copy()->endOfYear()
                : $start->copy()->month(6)->endOfMonth())
            : $start->copy()->addYear()->month(6)->endOfMonth();

        $durationMonths = (($end->year - $start->year) * 12) + $end->month - $start->month + 1;

        if ($durationMonths < 1 || $end->lessThan($start)) {
            throw ValidationException::withMessages(['package_type' => 'Periode paket tidak valid.']);
        }

        $gradeCount = 1;
        $installmentCount = max(1, $installmentCount);
        $fees = collect();
        $details = [];

        for ($grade = $startGrade; $grade < $startGrade + $gradeCount; $grade++) {
            $fee = RegistrationFee::query()
                ->active()
                ->where(function ($query) {
                    $query->where('fee_type', 'belajar')
                        ->orWhereRaw('LOWER(fee_type_label) = ?', ['belajar'])
                        ->orWhereRaw('LOWER(name) = ?', ['belajar']);
                })
                ->where('education_level', $class->education_level)
                ->where('grade_level', $grade)
                ->where('billing_period', $package['billing_period'])
                ->first();

            if (! $fee) {
                throw ValidationException::withMessages([
                    'package_type' => "Tarif Belajar untuk {$class->education_level} kelas {$grade} belum tersedia atau tidak aktif. Silakan hubungi Admin QSEE.",
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
        }

        $maximumInstallments = min(5, (int) $fees->min('max_installments'), $durationMonths);
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

                $discount = min((int) $detail['amount_due'], $remainingDiscount);
                $detail['discount_amount'] += $discount;
                $detail['amount_due'] -= $discount;
                $remainingDiscount -= $discount;
            }
            unset($detail);
        }

        $total = array_sum(array_column($details, 'amount_due'));

        $transaction = StudentFeeTransaction::create([
            'student_id' => $student->id,
            'registration_payment_id' => null,
            'duration_years' => $package['years'],
            'duration_months' => $durationMonths,
            'package_type' => $packageType,
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
                'due_date' => $start->copy()->addMonthsNoOverflow($number - 1)->toDateString(),
                'amount' => $base + ($number <= $remainder ? 1 : 0),
                'status' => 'awaiting_payment',
            ]);
        }

        return $transaction;
    }
}
