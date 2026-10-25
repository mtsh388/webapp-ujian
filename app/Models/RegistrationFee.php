<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationFee extends Model
{
    protected $fillable = [
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

    public static function keyFor(string $educationLevel, int|string $gradeLevel): string
    {
        return $educationLevel . '-' . (int) $gradeLevel;
    }
}
