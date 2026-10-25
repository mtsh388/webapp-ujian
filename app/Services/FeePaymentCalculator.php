<?php

namespace App\Services;

use App\Models\RegistrationFee;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FeePaymentCalculator
{
    public function calculate(RegistrationFee $fee, ?string $promoCode = null, mixed $at = null): array
    {
        $baseAmount = (int) $fee->amount;
        $discountAmount = 0;
        $appliedCode = null;

        if (filled($promoCode)) {
            if ($fee->isRegistrationFee()) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Biaya Pendaftaran tidak dapat menggunakan kode promo.',
                ]);
            }

            if (! $fee->discount_enabled || blank($fee->promo_code)) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Promo tidak tersedia untuk biaya ini.',
                ]);
            }

            if (strcasecmp(trim($promoCode), trim($fee->promo_code)) !== 0) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Kode promo tidak valid.',
                ]);
            }

            $moment = $at ? Carbon::parse($at) : now();

            if (! $fee->promo_starts_at || ! $fee->promo_ends_at
                || $moment->lt($fee->promo_starts_at)
                || $moment->gt($fee->promo_ends_at)) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Kode promo belum berlaku atau masa berlakunya sudah berakhir.',
                ]);
            }

            $value = (int) $fee->discount_value;
            $discountAmount = $fee->discount_type === 'percentage'
                ? (int) floor($baseAmount * min($value, 100) / 100)
                : $value;
            $discountAmount = min(max($discountAmount, 0), $baseAmount);
            $appliedCode = $fee->promo_code;
        }

        return [
            'base_amount' => $baseAmount,
            'discount_amount' => $discountAmount,
            'amount_due' => $baseAmount - $discountAmount,
            'promo_code' => $appliedCode,
        ];
    }

    public function splitInstallments(RegistrationFee $fee, int $amountDue, int $count): array
    {
        $maximum = $fee->isRegistrationFee() ? 1 : max(1, (int) $fee->max_installments);

        if ($count < 1 || $count > 5 || $count > $maximum) {
            throw ValidationException::withMessages([
                'installments' => $fee->isRegistrationFee()
                    ? 'Biaya Pendaftaran harus dibayar penuh dan tidak dapat dicicil.'
                    : 'Jumlah cicilan melebihi batas yang ditetapkan untuk biaya ini.',
            ]);
        }

        $base = intdiv($amountDue, $count);
        $remainder = $amountDue % $count;
        $installments = [];

        for ($number = 1; $number <= $count; $number++) {
            $installments[] = $base + ($number <= $remainder ? 1 : 0);
        }

        return $installments;
    }
}
