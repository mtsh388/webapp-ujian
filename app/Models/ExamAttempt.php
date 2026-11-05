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
        'class_name_snapshot',
        'education_level_snapshot',
        'grade_level_snapshot',
        'attempt_number',
        'started_at',
        'submitted_at',
        'total_questions',
        'question_order',
        'option_order',
        'correct_answers',
        'wrong_answers',
        'unanswered',
        'total_score',
        'score',
        'duration_seconds',
        'status',
        'passed',
        'parent_result_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'question_order' => 'array',
            'option_order' => 'array',
            'total_score' => 'decimal:2',
            'score' => 'decimal:2',
            'passed' => 'boolean',
            'parent_result_sent_at' => 'datetime',
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

    public function violations()
    {
        return $this->hasMany(ExamViolation::class, 'exam_attempt_id');
    }

    public function classNameAtAttempt(): ?string
    {
        if ($this->class_name_snapshot) {
            return $this->class_name_snapshot;
        }

        if (!$this->started_at || !$this->student_id) {
            return $this->student?->classRoom?->name;
        }

        $attemptDate = $this->started_at->toDateString();
        $history = StudentClassHistory::query()
            ->with('classRoom')
            ->where('student_id', $this->student_id)
            ->whereDate('started_at', '<=', $attemptDate)
            ->where(function ($query) use ($attemptDate) {
                $query->whereNull('ended_at')
                    ->orWhereDate('ended_at', '>=', $attemptDate);
            })
            ->orderByDesc('started_at')
            ->first();

        return $history?->classRoom?->name
            ?? $this->student?->classRoom?->name;
    }

    public function isFinished(): bool
    {
        return $this->status === 'submitted';
    }
}
