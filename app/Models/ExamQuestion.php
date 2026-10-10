<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question_bank_id',
        'type',
        'question',
        'explanation',
        'score',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function questionBank()
    {
        return $this->belongsTo(
            QuestionBank::class,
            'question_bank_id'
        );
    }

    public function options()
    {
        return $this->hasMany(
            ExamQuestionOption::class,
            'exam_question_id'
        )->orderBy('sort_order');
    }

    public function answers()
    {
        return $this->hasMany(
            ExamAnswer::class,
            'exam_question_id'
        );
    }
}
