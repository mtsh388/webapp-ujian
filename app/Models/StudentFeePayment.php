<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeePayment extends Model
{
    protected $fillable = [
        'student_fee_transaction_id',
        'installment_number',
        'amount',
        'status',
        'proof_path',
        'rejection_reason',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'amount' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(StudentFeeTransaction::class, 'student_fee_transaction_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}