<?php

namespace App\Repositories;

use App\Models\ParentModel;
use Illuminate\Database\Eloquent\Builder;

class ParentRepository
{
    public function __construct(
        protected ParentModel $model
    )
    {
    }

    public function query(): Builder
    {
        return $this->model
            ->newQuery()
            ->withCount('students');
    }

    public function findOrFail(int $id): ParentModel
    {
        return $this->model
            ->with('students')
            ->findOrFail($id);
    }

    public function create(array $data): ParentModel
    {
        return $this->model->create($data);
    }

    public function update(
        ParentModel $parent,
        array       $data
    ): bool
    {
        return $parent->update($data);
    }

    public function delete(ParentModel $parent): bool
    {
        return $parent->delete();
    }

    public function existsByUserId(
        int  $userId,
        ?int $exceptId = null
    ): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->when(
                $exceptId,
                fn($query) => $query->where('id', '!=', $exceptId)
            )
            ->exists();
    }

    public function existsByFullNameAndPhone(
        string  $fullName,
        ?string $phone = null,
        ?int    $exceptId = null
    ): bool
    {
        return $this->model
            ->whereRaw('LOWER(full_name) = ?', [
                strtolower($fullName),
            ])
            ->when(
                $phone,
                fn($query) => $query->where('phone', $phone)
            )
            ->when(
                $exceptId,
                fn($query) => $query->where('id', '!=', $exceptId)
            )
            ->exists();
    }

    public function syncStudents(
        ParentModel $parent,
        array       $students
    ): void
    {
        $parent->students()->sync($students);
    }
}
