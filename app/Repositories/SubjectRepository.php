<?php

namespace App\Repositories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;

class SubjectRepository
{
    public function __construct(
        protected Subject $model
    ) {
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function findOrFail(int $id): Subject
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Subject
    {
        return $this->model->create($data);
    }

    public function update(Subject $subject, array $data): bool
    {
        return $subject->update($data);
    }

    public function delete(Subject $subject): bool
    {
        return $subject->delete();
    }

    public function restore(Subject $subject): bool
    {
        return $subject->restore();
    }
}
