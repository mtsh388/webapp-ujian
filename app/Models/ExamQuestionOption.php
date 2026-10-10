<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_question_id',
        'option_key',
        'option_text',
        'is_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function examQuestion()
    {
        return $this->belongsTo(ExamQuestion::class);
    }

    public function answers()
    {
        return $this->hasMany(
            ExamAnswer::class,
            'selected_option_id'
        );
    }
}
