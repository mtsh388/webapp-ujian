<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingGenerationSchedule extends Model
{
    protected $fillable = [
        'fee_rate_id', 'period_label', 'scheduled_at', 'due_date', 'is_active',
        'last_run_at', 'generated_count', 'last_result', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'due_date' => 'date',
            'last_run_at' => 'datetime',
            'is_active' => 'boolean',
            'generated_count' => 'integer',
        ];
    }

    public function feeRate(): BelongsTo
    {
        return $this->belongsTo(BillingFeeRate::class, 'fee_rate_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
