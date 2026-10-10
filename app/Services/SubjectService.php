<?php

namespace App\Services;

use App\Models\Subject;
use App\Repositories\SubjectRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubjectService
{
    public function __construct(
        protected SubjectRepository $repository
    )
    {
    }

    public function getDataTable(
        Request $request
    ): array
    {
        $query = $this->repository->query();

        $total = $query->count();

        $search = $request->input('search.value');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $filtered = $query->count();

        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'code',
            3 => 'description',
            4 => 'is_active',
            5 => 'id',
        ];

        $columnIndex = (int)$request->input(
            'order.0.column',
            1
        );

        $orderColumn = $columns[$columnIndex] ?? 'name';

        $orderDirection = $request->input(
            'order.0.dir',
            'asc'
        );

        $orderDirection = in_array(
            $orderDirection,
            ['asc', 'desc'],
            true
        )
            ? $orderDirection
            : 'asc';

        $start = max(
            0,
            (int)$request->input('start', 0)
        );

        $length = (int)$request->input(
            'length',
            10
        );

        if ($length < 1) {
            $length = 10;
        }

        $subjects = $query
            ->orderBy(
                $orderColumn,
                $orderDirection
            )
            ->skip($start)
            ->take($length)
            ->get();

        $data = $subjects
            ->values()
            ->map(function (
                Subject $subject,
                int     $index
            ) use ($start) {
                return [
                    'no' => $start + $index + 1,
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'description' => $subject->description,
                    'is_active' => $subject->is_active,
                ];
            })
            ->values()
            ->all();

        return [
            'draw' => (int)$request->input(
                'draw'
            ),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }

    public function create(
        array $data
    ): Subject
    {
        $data['code'] = strtoupper(
            trim($data['code'])
        );

        if (
            $this->repository->existsByCode(
                $data['code']
            )
        ) {
            throw ValidationException::withMessages([
                'code' => 'Kode mata pelajaran sudah digunakan.',
            ]);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(
            function () use ($data) {
                return $this->repository->create(
                    $data
                );
            }
        );
    }

    public function update(
        Subject $subject,
        array   $data
    ): Subject
    {
        $data['code'] = strtoupper(
            trim($data['code'])
        );

        if (
            $this->repository->existsByCode(
                $data['code'],
                $subject->id
            )
        ) {
            throw ValidationException::withMessages([
                'code' => 'Kode mata pelajaran sudah digunakan.',
            ]);
        }

        $data['is_active'] = $data['is_active'] ?? false;

        DB::transaction(
            function () use (
                $subject,
                $data
            ) {
                $this->repository->update(
                    $subject,
                    $data
                );
            }
        );

        return $subject->refresh();
    }

    public function delete(
        Subject $subject
    ): bool
    {
        return DB::transaction(
            function () use ($subject) {
                return $this->repository->delete(
                    $subject
                );
            }
        );
    }

    public function updateStatus(
        Subject $subject,
        bool    $isActive
    ): Subject
    {
        $this->repository->update(
            $subject,
            [
                'is_active' => $isActive,
            ]
        );

        return $subject->refresh();
    }
}
