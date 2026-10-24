<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInvoice extends Model
{
    protected $fillable = [
        'student_id',
        'fee_rate_id',
        'title',
        'period_type',
        'period_label',
        'amount',
        'due_date',
        'notes',
        'payment_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function feeRate(): BelongsTo
    {
        return $this->belongsTo(BillingFeeRate::class, 'fee_rate_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class, 'invoice_id')->latest('paid_at')->latest('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(BillingPayment::class, 'invoice_id')->where('status', 'verified');
    }

    public function getVerifiedAmountAttribute(): string
    {
        return (string) $this->verifiedPayments()->sum('amount');
    }

    public function getOutstandingAmountAttribute(): string
    {
        return (string) max(0, (float) $this->amount - (float) $this->verified_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->payment_status !== 'paid' && $this->due_date->isPast() && !$this->due_date->isToday();
    }

    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->verifiedPayments()->sum('amount');

        $this->forceFill([
            'payment_status' => $paid >= (float) $this->amount ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
        ])->save();
    }
}
