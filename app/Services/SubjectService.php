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
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('education_level', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();

        $columns = [
            0 => 'id',
            1 => 'code',
            2 => 'name',
            3 => 'education_level',
            4 => 'grade_level',
            5 => 'description',
            6 => 'is_active',
            7 => 'id',
        ];

        $columnIndex = (int) $request->input('order.0.column', 2);
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
                'code' => $subject->code,
                'name' => $subject->name,
                'education_level' => $subject->education_level,
                'grade_level' => $subject->grade_level,
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
        $data['topic'] = $data['name'];
        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(function () use ($data) {
            $data['code'] = $this->generateCode($data);

            return $this->repository->create($data);
        });
    }

    public function update(Subject $subject, array $data): Subject
    {
        $data['name'] = trim($data['name']);
        $data['education_level'] = strtoupper(trim($data['education_level']));
        $data['topic'] = $subject->topic ?: $data['name'];
        $data['is_active'] = $data['is_active'] ?? false;

        DB::transaction(fn () => $this->repository->update($subject, $data));

        return $subject->refresh();
    }

    private function generateCode(array $data): string
    {
        $level = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($data['education_level'] ?? ''))) ?: 'UMUM';
        $grade = (string) ($data['grade_level'] ?? 0);
        $normalizedName = strtoupper(\Illuminate\Support\Str::ascii((string) ($data['name'] ?? 'TOPIK')));
        $words = preg_split('/\s+/', trim($normalizedName)) ?: [];
        $words = array_values(array_filter($words, fn ($word) => $word !== '' && preg_match('/[A-Z0-9]/', $word)));

        if (count($words) > 1) {
            $abbreviation = substr(implode('', array_map(fn ($word) => substr($word, 0, 1), $words)), 0, 5);
        } else {
            $abbreviation = substr($words[0] ?? 'TOP', 0, 3);
        }

        $abbreviation = preg_replace('/[^A-Z0-9]/', '', $abbreviation) ?: 'TOP';
        $prefix = $level . '-' . $grade . '-' . $abbreviation;
        $existingCodes = Subject::query()
            ->where('code', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->pluck('code');

        $nextNumber = 1;

        foreach ($existingCodes as $existingCode) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $existingCode, $matches)) {
                $nextNumber = max($nextNumber, ((int) $matches[1]) + 1);
            }
        }

        do {
            $code = $prefix . '-' . str_pad((string) $nextNumber++, 3, '0', STR_PAD_LEFT);
        } while (Subject::withTrashed()->where('code', $code)->exists());

        return $code;
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
