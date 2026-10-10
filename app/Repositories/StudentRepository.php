<?php

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class StudentRepository
{
    public function __construct(
        protected Student $model
    )
    {
    }

    public function query(): Builder
    {
        return $this->model
            ->newQuery()
            ->with('classRoom');
    }

    public function findOrFail(int $id): Student
    {
        return $this->model
            ->with('classRoom')
            ->findOrFail($id);
    }

    public function create(array $data): Student
    {
        return $this->model->create($data);
    }

    public function update(
        Student $student,
        array   $data
    ): bool
    {
        return $student->update($data);
    }

    public function delete(Student $student): bool
    {
        return $student->delete();
    }

    public function existsByStudentNumber(
        string $studentNumber,
        ?int   $exceptId = null
    ): bool
    {
        return $this->model
            ->where('student_number', $studentNumber)
            ->when(
                $exceptId,
                fn($query) => $query->where('id', '!=', $exceptId)
            )
            ->exists();
    }
}
