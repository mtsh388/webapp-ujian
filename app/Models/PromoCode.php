<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValidAt($at = null): bool
    {
        $at = $at ? \Illuminate\Support\Carbon::parse($at) : now();

        return $this->is_active
            && $at->greaterThanOrEqualTo($this->starts_at)
            && $at->lessThanOrEqualTo($this->ends_at);
    }

    public function discountFor(int $amount): int
    {
        $value = max(0, (int) $this->discount_value);
        $discount = $this->discount_type === 'percentage'
            ? (int) floor($amount * min($value, 100) / 100)
            : $value;

        return min($amount, $discount);
    }
}
