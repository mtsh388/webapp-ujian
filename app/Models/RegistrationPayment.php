<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationPayment extends Model
{
    protected $fillable = [
        'user_id',
        'student_id',
        'amount',
        'status',
        'proof_path',
        'payment_type',
        'promo_code',
        'installment_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
