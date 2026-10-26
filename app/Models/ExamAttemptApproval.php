<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttemptApproval extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'status',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
