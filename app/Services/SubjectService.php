<?php

namespace App\\Services;

use App\\Models\\Subject;
use App\\Repositories\\SubjectRepository;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;

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
        $data = $this->normalizeSubjectData($data, true);
        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(function () use ($data) {
            $data['code'] = $this->generateCode($data);

            return $this->repository->create($data);
        });
    }

    public function update(Subject $subject, array $data): Subject
    {
        $data = $this->normalizeSubjectData($data);
        $data['is_active'] = $data['is_active'] ?? false;

        DB::transaction(fn () => $this->repository->update($subject, $data));

        return $subject->refresh();
    }

    private function normalizeSubjectData(array $data, bool $creating = false): array
    {
        $data['name'] = trim((string) $data['name']);
        $data['education_level'] = strtoupper(trim((string) $data['education_level']));
        $data['grade_level'] = (int) $data['grade_level'];

        if (array_key_exists('topic', $data)) {
            $data['topic'] = trim((string) $data['topic']);
        } elseif ($creating) {
            $data['topic'] = $data['name'];
        }

        return $data;
    }

    private function generateCode(array $data): string
    {
        $level = preg_replace(
            '/[^A-Z0-9]/',
            '',
            strtoupper((string) ($data['education_level'] ?? ''))
        ) ?: 'UMUM';
        $grade = (string) ($data['grade_level'] ?? 0);
        $prefix = $level . '-' . $grade . '-' . $this->abbreviation((string) ($data['name'] ?? 'TOPIK'));

        $existingCodes = Subject::withTrashed()
            ->lockForUpdate()
            ->pluck('code');

        $usedCodes = [];
        $nextNumber = 1;

        foreach ($existingCodes as $existingCode) {
            if (! is_string($existingCode) || trim($existingCode) === '') {
                continue;
            }

            $normalizedCode = strtoupper(trim($existingCode));
            $usedCodes[$normalizedCode] = true;

            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\\d+)$/i', $normalizedCode, $matches)) {
                $nextNumber = max($nextNumber, ((int) $matches[1]) + 1);
            }
        }

        do {
            $code = $prefix . '-' . str_pad((string) $nextNumber++, 3, '0', STR_PAD_LEFT);
        } while (isset($usedCodes[strtoupper($code)]));

        return $code;
    }

    private function abbreviation(string $topic): string
    {
        $normalized = strtoupper(Str::ascii(trim($topic)));
        $words = preg_split('/[^A-Z0-9]+/', $normalized) ?: [];
        $words = array_values(array_filter($words, fn (string $word) => $word !== ''));

        if (count($words) > 1) {
            $value = substr(implode('', array_map(
                fn (string $word) => substr($word, 0, 1),
                $words
            )), 0, 5);
        } else {
            $value = substr($words[0] ?? 'TOP', 0, 3);
        }

        return preg_replace('/[^A-Z0-9]/', '', $value) ?: 'TOP';
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
