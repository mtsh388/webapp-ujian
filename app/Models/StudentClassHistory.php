<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class StudentClassHistory extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'education_level',
        'grade_level',
        'started_at',
        'ended_at',
        'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
}
