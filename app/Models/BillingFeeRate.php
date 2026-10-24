<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingFeeRate extends Model
{
    protected $fillable = [
        'fee_type_id', 'academic_year_id', 'class_id', 'period_type',
        'period_label', 'amount', 'due_day', 'is_active',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_active' => 'boolean', 'due_day' => 'integer'];
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(BillingFeeType::class, 'fee_type_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'fee_rate_id');
    }
}
