<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'total_questions',
        'correct_answers',
        'wrong_answers',
        'unanswered',
        'total_score',
        'score',
        'duration_seconds',
        'status',
        'passed',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'total_score' => 'decimal:2',
            'score' => 'decimal:2',
            'passed' => 'boolean',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function isFinished(): bool
    {
        return $this->status === 'submitted';
    }
}
