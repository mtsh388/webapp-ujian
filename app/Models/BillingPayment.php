<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'paid_at',
        'reference',
        'notes',
        'status',
        'recorded_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
