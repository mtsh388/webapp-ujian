<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'event_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            'tab_hidden' => 'Berpindah tab / halaman',
            'fullscreen_exit' => 'Keluar dari fullscreen',
            default => 'Aktivitas perlu ditinjau',
        };
    }
}
