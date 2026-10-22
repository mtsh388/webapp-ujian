<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'class_id',
        'student_number',
        'full_name',
        'school_name',
        'education_level',
        'gender',
        'birth_date',
        'phone',
        'parent_phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function parents()
    {
        return $this->belongsToMany(
            ParentModel::class,
            'student_parent',
            'student_id',
            'parent_id'
        )->withPivot([
            'relationship',
            'is_primary',
        ])->withTimestamps();
    }

    public function examParticipants()
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
