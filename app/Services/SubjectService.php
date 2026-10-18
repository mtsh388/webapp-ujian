<?php

namespace App\Services;

use App\Models\Subject;
use App\Repositories\SubjectRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectService
{
    public function __construct(
        protected SubjectRepository $repository
    ) {
    }

    public function getDataTable(Request $request): array
    {
        $query = $this->repository->query();
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('education_level', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();

        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'education_level',
            3 => 'topic',
            4 => 'description',
            5 => 'is_active',
            6 => 'id',
        ];

        $columnIndex = (int) $request->input('order.0.column', 1);
        $orderColumn = $columns[$columnIndex] ?? 'name';
        $orderDirection = $request->input('order.0.dir', 'asc');
        $orderDirection = in_array($orderDirection, ['asc', 'desc'], true)
            ? $orderDirection
            : 'asc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : $length;

        $subjects = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $subjects->values()->map(function (Subject $subject, int $index) use ($start) {
            return [
                'no' => $start + $index + 1,
                'id' => $subject->id,
                'name' => $subject->name,
                'education_level' => $subject->education_level,
                'topic' => $subject->topic,
                'description' => $subject->description,
                'is_active' => $subject->is_active,
            ];
        })->all();

        return [
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }

    public function create(array $data): Subject
    {
        $data['name'] = trim($data['name']);
        $data['education_level'] = strtoupper(trim($data['education_level']));
        $data['topic'] = trim($data['topic']);
        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(fn () => $this->repository->create($data));
    }

    public function update(Subject $subject, array $data): Subject
    {
        $data['name'] = trim($data['name']);
        $data['education_level'] = strtoupper(trim($data['education_level']));
        $data['topic'] = trim($data['topic']);
        $data['is_active'] = $data['is_active'] ?? false;

        DB::transaction(fn () => $this->repository->update($subject, $data));

        return $subject->refresh();
    }

    public function delete(Subject $subject): bool
    {
        return DB::transaction(fn () => $this->repository->delete($subject));
    }

    public function updateStatus(Subject $subject, bool $isActive): Subject
    {
        $this->repository->update($subject, ['is_active' => $isActive]);

        return $subject->refresh();
    }
}
