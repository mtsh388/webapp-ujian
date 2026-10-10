<?php

namespace App\Services;

use App\Models\ParentModel;
use App\Repositories\ParentRepository;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParentService
{
    public function __construct(
        protected ParentRepository $repository,
        protected AccountService   $accountService
    )
    {
    }

    public function getDataTable(Request $request): array
    {
        $query = $this->repository->query();

        $total = $this->repository
            ->query()
            ->count();

        $search = trim(
            $request->input('search.value', '')
        );

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $filtered = $query->count();

        $columns = [
            0 => 'id',
            1 => 'full_name',
            2 => 'phone',
            3 => 'students_count',
            4 => 'is_active',
            5 => 'id',
        ];

        $columnIndex = (int)$request->input(
            'order.0.column',
            1
        );

        $orderColumn = $columns[$columnIndex] ?? 'full_name';

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

        $parents = $query
            ->orderBy(
                $orderColumn,
                $orderDirection
            )
            ->skip($start)
            ->take($length)
            ->get();

        $data = $parents
            ->values()
            ->map(function (
                ParentModel $parent,
                int         $index
            ) use ($start) {
                return [
                    'no' => $start + $index + 1,
                    'id' => $parent->id,
                    'full_name' => $parent->full_name,
                    'phone' => $parent->phone,
                    'address' => $parent->address,
                    'students_count' => $parent->students_count,
                    'is_active' => $parent->is_active,
                ];
            })
            ->all();

        return [
            'draw' => (int)$request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }

    public function create(array $data): ParentModel
    {
        $data['full_name'] = trim(
            $data['full_name']
        );

        if (!empty($data['phone'])) {
            $data['phone'] = trim(
                $data['phone']
            );
        }

        $data['is_active'] = $data['is_active'] ?? true;

        $students = $data['students'] ?? [];

        unset(
            $data['students'],
            $data['user_id']
        );

        return DB::transaction(function () use (
            $data,
            $students
        ) {
            $user = $this->accountService->createParentAccount(
                $data['full_name'],
                $data['phone'] ?? null
            );

            $data['user_id'] = $user->id;

            $parent = $this->repository->create($data);

            if (!empty($students)) {
                $this->repository->syncStudents(
                    $parent,
                    $students
                );
            }

            return $parent->load('user', 'students');
        });
    }

    public function update(
        ParentModel $parent,
        array       $data
    ): ParentModel
    {
        $data['full_name'] = trim(
            $data['full_name']
        );

        if (!empty($data['phone'])) {
            $data['phone'] = trim(
                $data['phone']
            );
        }

        $students = $data['students'] ?? [];

        unset(
            $data['students'],
            $data['user_id']
        );

        DB::transaction(function () use (
            $parent,
            $data,
            $students
        ) {
            $this->repository->update(
                $parent,
                $data
            );

            if ($parent->user) {
                $this->accountService->updateAccount(
                    $parent->user,
                    $data['full_name']
                );
            }

            $this->repository->syncStudents(
                $parent,
                $students
            );
        });

        return $parent
            ->refresh()
            ->load('user', 'students');
    }

    public function delete(ParentModel $parent): bool
    {
        return DB::transaction(
            fn() => $this->repository->delete($parent)
        );
    }

    public function updateStatus(
        ParentModel $parent,
        bool        $isActive
    ): ParentModel
    {
        $this->repository->update(
            $parent,
            [
                'is_active' => $isActive,
            ]
        );

        return $parent->refresh();
    }

    public function getDetail(
        ParentModel $parent
    ): ParentModel
    {
        return $parent->load([
            'user',
            'students.classRoom',
        ]);
    }
}
