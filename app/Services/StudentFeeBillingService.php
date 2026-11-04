<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\PromoCode;
use App\Models\RegistrationFee;
use App\Models\Student;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StudentFeeBillingService
{
    public function previewForStudent(Student $student, string $packageType, int $installmentCount, ?string $promoCode = null): array
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

        $today = Carbon::today();
        $start = $student->is_active && $student->access_expires_at && $student->access_expires_at->greaterThanOrEqualTo($today)
            ? $student->access_expires_at->copy()->addDay()->startOfDay()
            : $today->copy();

        $class = $this->classForBillingPeriod($student, $start);
        $startGrade = (int) $class->grade_level;
        $isOddSemester = $start->month >= 7;

        if ($packageType === 'yearly' && ! $isOddSemester) {
            throw ValidationException::withMessages([
                'package_type' => 'Paket tahunan hanya dapat dipilih pada semester ganjil (Juli–Desember).',
            ]);
        }

        if ($class->education_level === 'SMA' && $startGrade === 12 && $packageType !== 'yearly') {
            throw ValidationException::withMessages([
                'package_type' => 'Siswa SMA kelas 12 hanya dapat memilih paket tahunan pada semester ganjil.',
            ]);
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

        $installmentCount = max(1, $installmentCount);
        $fees = collect();
        $details = [];

        $fee = RegistrationFee::query()
            ->active()
            ->where(function ($query) {
                $query->where('fee_type', 'belajar')
                    ->orWhereRaw('LOWER(fee_type_label) = ?', ['belajar'])
                    ->orWhereRaw('LOWER(name) = ?', ['belajar']);
            })
            ->where('education_level', $class->education_level)
            ->where('grade_level', $startGrade)
            ->where('billing_period', $package['billing_period'])
            ->first();

        if (! $fee) {
            throw ValidationException::withMessages([
                'package_type' => "Tarif Belajar untuk {$class->education_level} kelas {$startGrade} belum tersedia atau tidak aktif. Silakan hubungi Admin QSEE.",
            ]);
        }

        $fees->push($fee);
        $amount = (int) $fee->amount;
        $details[] = [
            'fee_id' => $fee->id,
            'name' => $fee->fee_type_label ?: $fee->name,
            'education_level' => $class->education_level,
            'grade_level' => $startGrade,
            'amount' => $amount,
            'discount_amount' => 0,
            'amount_due' => $amount,
        ];

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

            $eligiblePromoAmount = (int) $fees->filter(fn ($item) => $item->discount_enabled)->sum('amount');
            if ($eligiblePromoAmount <= 0) {
                throw ValidationException::withMessages(['promo_code' => 'Tarif Belajar yang dipilih tidak mengizinkan diskon/promo.']);
            }

            $remainingDiscount = $promo->discountFor($eligiblePromoAmount);
            foreach ($details as &$detail) {
                $selectedFee = $fees->firstWhere('id', $detail['fee_id']);
                if (! $selectedFee->discount_enabled || $remainingDiscount <= 0) {
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
        $base = intdiv($total, $installmentCount);
        $remainder = $total % $installmentCount;
        $installments = [];

        for ($number = 1; $number <= $installmentCount; $number++) {
            $installments[] = [
                'number' => $number,
                'due_date' => $start->copy()->addMonthsNoOverflow($number - 1)->toDateString(),
                'amount' => $base + ($number <= $remainder ? 1 : 0),
            ];
        }

        return [
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'class_name' => $class->name,
            'education_level' => $class->education_level,
            'grade_level' => $startGrade,
            'package_type' => $packageType,
            'package_label' => $package['label'],
            'period_starts_at' => $start->toDateString(),
            'period_ends_at' => $end->toDateString(),
            'duration_months' => $durationMonths,
            'installment_count' => $installmentCount,
            'amount' => $total,
            'promo_code' => $promo?->code,
            'details' => $details,
            'installments' => $installments,
        ];
    }

    public function generateForStudent(Student $student, string $packageType, int $installmentCount, ?string $promoCode = null): StudentFeeTransaction
    {
        $quote = $this->previewForStudent($student, $packageType, $installmentCount, $promoCode);

        $transaction = StudentFeeTransaction::create([
            'student_id' => $student->id,
            'class_name_snapshot' => $quote['class_name'],
            'education_level_snapshot' => $quote['education_level'],
            'grade_level_snapshot' => $quote['grade_level'],
            'registration_payment_id' => null,
            'duration_years' => 1,
            'duration_months' => $quote['duration_months'],
            'package_type' => $quote['package_type'],
            'installment_count' => $quote['installment_count'],
            'amount' => $quote['amount'],
            'amount_paid' => 0,
            'details' => $quote['details'],
            'promo_code' => $quote['promo_code'],
            'period_starts_at' => $quote['period_starts_at'],
            'period_ends_at' => $quote['period_ends_at'],
            'status' => 'awaiting_payment',
        ]);

        foreach ($quote['installments'] as $installment) {
            StudentFeePayment::create([
                'student_fee_transaction_id' => $transaction->id,
                'installment_number' => $installment['number'],
                'due_date' => $installment['due_date'],
                'amount' => $installment['amount'],
                'status' => 'awaiting_payment',
            ]);
        }

        return $transaction;
    }

    private function classForBillingPeriod(Student $student, Carbon $periodStart): ClassRoom
    {
        $currentClass = $student->classRoom;

        if (
            ! $student->access_expires_at
            || $student->access_expires_at->toDateString() !== $periodStart->copy()->subDay()->toDateString()
            || $periodStart->month !== 7
            || $periodStart->day !== 1
        ) {
            return $currentClass;
        }

        $lastAcademicYearHistory = $student->classHistories()
            ->whereDate('ended_at', $periodStart->copy()->subDay()->toDateString())
            ->latest('id')
            ->first();

        if ($lastAcademicYearHistory && $lastAcademicYearHistory->class_id && (int) $lastAcademicYearHistory->class_id !== (int) $currentClass->id) {
            return $currentClass;
        }

        [$nextLevel, $nextGrade] = match ([$currentClass->education_level, (int) $currentClass->grade_level]) {
            ['SD', 6] => ['SMP', 7],
            ['SMP', 9] => ['SMA', 10],
            ['SMA', 12] => [null, null],
            default => [$currentClass->education_level, (int) $currentClass->grade_level + 1],
        };

        if ($nextLevel === null) {
            throw ValidationException::withMessages([
                'package_type' => 'Siswa SMA kelas 12 tidak dapat membuat tagihan untuk tahun ajaran berikutnya karena telah menyelesaikan jenjang SMA.',
            ]);
        }

        $nextClass = ClassRoom::query()
            ->where('education_level', $nextLevel)
            ->where('grade_level', $nextGrade)
            ->where('registration_type', $currentClass->registration_type)
            ->where('is_active', true)
            ->first();

        if (! $nextClass) {
            throw ValidationException::withMessages([
                'package_type' => "Kelas tujuan {$nextLevel} {$nextGrade} belum tersedia atau tidak aktif. Tagihan tidak dibuat agar tarif kelas sebelumnya tidak digunakan.",
            ]);
        }

        return $nextClass;
    }
}
