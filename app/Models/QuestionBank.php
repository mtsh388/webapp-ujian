<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'code',
        'type',
        'question',
        'explanation',
        'difficulty',
        'default_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)
            ->orderBy('sort_order');
    }

    public function media()
    {
        return $this->hasMany(QuestionMedia::class, 'question_bank_id')
            ->orderBy('sort_order');
    }

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class);
    }
}
