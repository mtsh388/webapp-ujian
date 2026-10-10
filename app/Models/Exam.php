<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'code',
        'title',
        'description',
        'duration_minutes',
        'start_at',
        'end_at',
        'passing_score',
        'max_attempts',
        'shuffle_questions',
        'shuffle_options',
        'show_result_immediately',
        'show_correct_answers',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'passing_score' => 'decimal:2',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_result_immediately' => 'boolean',
            'show_correct_answers' => 'boolean',
        ];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order');
    }

    public function participants()
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
