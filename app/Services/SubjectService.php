<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Subject;
use App\Repositories\SubjectRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubjectService
{
    public function __construct(
        protected SubjectRepository $repository
    ) {
    }

    public function getDataTable(Request $request): array
    {
        $query = $this->repository->query()->with('classRoom');
        $total = $query->count();
        $search = $request->input('search.value');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('education_level', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $filtered = $query->count();

        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'education_level',
            3 => 'class_id',
            4 => 'topic',
            5 => 'description',
            6 => 'is_active',
            7 => 'id',
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
                'class_name' => $subject->classRoom?->name,
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
        $data['topic'] = trim($data['topic']);
        $data['education_level'] = ClassRoom::query()->findOrFail($data['class_id'])->education_level;
        $data['code'] = $this->makeUniqueCode($data['topic']);
        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update(Subject $subject, array $data): Subject
    {
        $data['topic'] = trim($data['topic']);
        $data['education_level'] = ClassRoom::query()->findOrFail($data['class_id'])->education_level;
        $data['code'] = $this->makeUniqueCode($data['topic'], $subject->id);
        $data['is_active'] = $data['is_active'] ?? false;

        DB::transaction(function () use ($subject, $data) {
            $this->repository->update($subject, $data);
        });

        return $subject->refresh();
    }

    protected function makeUniqueCode(string $topic, ?int $exceptId = null): string
    {
        $base = Str::upper(Str::slug($topic, '-'));
        $base = substr($base !== '' ? $base : 'TOPIK', 0, 50);
        $candidate = $base;
        $suffix = 2;

        while ($this->repository->existsByCode($candidate, $exceptId)) {
            $ending = '-' . $suffix++;
            $candidate = substr($base, 0, 50 - strlen($ending)) . $ending;
        }

        return $candidate;
    }

    public function delete(Subject $subject): bool
    {
        return DB::transaction(function () use ($subject) {
            return $this->repository->delete($subject);
        });
    }

    public function updateStatus(Subject $subject, bool $isActive): Subject
    {
        $this->repository->update($subject, ['is_active' => $isActive]);

        return $subject->refresh();
    }
}
