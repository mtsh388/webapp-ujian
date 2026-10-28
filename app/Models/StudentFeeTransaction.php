<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeeTransaction extends Model
{
    protected $fillable = [
        'student_id',
        'registration_payment_id',
        'duration_years',
        'installment_count',
        'amount',
        'amount_paid',
        'details',
        'promo_code',
        'period_starts_at',
        'period_ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'duration_years' => 'integer',
            'installment_count' => 'integer',
            'amount' => 'integer',
            'amount_paid' => 'integer',
            'details' => 'array',
            'period_starts_at' => 'date',
            'period_ends_at' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function registrationPayment()
    {
        return $this->belongsTo(RegistrationPayment::class);
    }

    public function installments()
    {
        return $this->hasMany(StudentFeePayment::class)->orderBy('installment_number');
    }
}