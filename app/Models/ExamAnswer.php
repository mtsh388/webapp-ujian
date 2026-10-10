<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'exam_question_id',
        'selected_option_id',
        'is_correct',
        'score',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'score' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function attempt()
    {
        return $this->belongsTo(
            ExamAttempt::class,
            'exam_attempt_id'
        );
    }

    public function examQuestion()
    {
        return $this->belongsTo(
            ExamQuestion::class,
            'exam_question_id'
        );
    }

    public function selectedOption()
    {
        return $this->belongsTo(
            ExamQuestionOption::class,
            'selected_option_id'
        );
    }

    public function question()
    {
        return $this->hasOneThrough(
            QuestionBank::class,
            ExamQuestion::class,
            'id',
            'id',
            'exam_question_id',
            'question_bank_id'
        );
    }
}
