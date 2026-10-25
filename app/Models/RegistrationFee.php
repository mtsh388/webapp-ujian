<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationFee extends Model
{
    protected $fillable = [
        'fee_type',
        'name',
        'description',
        'billing_period',
        'education_level',
        'grade_level',
        'amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'amount' => 'integer',
            'is_active' => 'boolean',
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
