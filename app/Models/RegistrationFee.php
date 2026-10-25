<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationFee extends Model
{
    protected $fillable = [
        'fee_type',
        'fee_type_label',
        'name',
        'description',
        'billing_period',
        'education_level',
        'grade_level',
        'amount',
        'is_active',
        'discount_enabled',
        'promo_code',
        'promo_starts_at',
        'promo_ends_at',
        'discount_type',
        'discount_value',
        'max_installments',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'amount' => 'integer',
            'is_active' => 'boolean',
            'discount_enabled' => 'boolean',
            'promo_starts_at' => 'datetime',
            'promo_ends_at' => 'datetime',
            'discount_value' => 'integer',
            'max_installments' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRegistration($query)
    {
        return $query->where('fee_type', 'registration');
    }

    public function isRegistrationFee(): bool
    {
        return $this->fee_type === 'registration';
    }

    public function promoIsCurrentlyValid(?string $code = null, $at = null): bool
    {
        if ($this->isRegistrationFee() || ! $this->discount_enabled || blank($this->promo_code)) {
            return false;
        }

        if ($code !== null && strcasecmp(trim($code), trim($this->promo_code)) !== 0) {
            return false;
        }

        $at = $at ? \Illuminate\Support\Carbon::parse($at) : now();

        return $this->promo_starts_at
            && $this->promo_ends_at
            && $at->greaterThanOrEqualTo($this->promo_starts_at)
            && $at->lessThanOrEqualTo($this->promo_ends_at);
    }

    public function discountAmount(): int
    {
        if (! $this->discount_enabled || $this->isRegistrationFee()) {
            return 0;
        }

        $value = (int) ($this->discount_value ?? 0);
        $discount = $this->discount_type === 'percentage'
            ? (int) floor($this->amount * min($value, 100) / 100)
            : $value;

        return min(max($discount, 0), $this->amount);
    }

    public static function keyFor(string $educationLevel, int|string $gradeLevel): string
    {
        return $educationLevel . '-' . (int) $gradeLevel;
    }

    public static function typeLabels(): array
    {
        return [
            'registration' => 'Pendaftaran',
            'monthly' => 'SPP',
            'exam' => 'Ujian',
            'books' => 'Buku dan Modul',
            'uniform' => 'Seragam',
            'activity' => 'Kegiatan',
            'other' => 'Lainnya',
        ];
    }
}
