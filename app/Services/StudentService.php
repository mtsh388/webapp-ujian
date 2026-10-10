<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\StudentRepository;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function __construct(
        protected StudentRepository $repository,
        protected AccountService    $accountService
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
                    ->where('student_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('gender', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('classRoom', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $filtered = $query->count();

        $columns = [
            0 => 'id',
            1 => 'student_number',
            2 => 'full_name',
            3 => 'gender',
            4 => 'class_id',
            5 => 'phone',
            6 => 'is_active',
            7 => 'id',
        ];

        $columnIndex = (int)$request->input(
            'order.0.column',
            2
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

        $students = $query
            ->orderBy(
                $orderColumn,
                $orderDirection
            )
            ->skip($start)
            ->take($length)
            ->get();

        $data = $students
            ->values()
            ->map(function (
                Student $student,
                int     $index
            ) use ($start) {
                return [
                    'no' => $start + $index + 1,
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'gender' => $student->gender,
                    'birth_date' => $student->birth_date?->format('Y-m-d'),
                    'phone' => $student->phone,
                    'address' => $student->address,
                    'class_id' => $student->class_id,
                    'class_name' => $student->classRoom?->name,
                    'is_active' => $student->is_active,
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

    public function create(array $data): Student
    {
        $data['student_number'] = trim(
            $data['student_number']
        );

        $data['full_name'] = trim(
            $data['full_name']
        );

        if (
            $this->repository->existsByStudentNumber(
                $data['student_number']
            )
        ) {
            throw ValidationException::withMessages([
                'student_number' => 'Nomor siswa sudah digunakan.',
            ]);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(function () use ($data) {
            $user = $this->accountService->createStudentAccount(
                $data['full_name'],
                $data['student_number']
            );

            $studentData = $data;

            unset($studentData['user_id']);

            $studentData['user_id'] = $user->id;

            return $this->repository->create($studentData);
        });
    }

    public function update(
        Student $student,
        array   $data
    ): Student
    {
        $data['student_number'] = trim(
            $data['student_number']
        );

        $data['full_name'] = trim(
            $data['full_name']
        );

        if (
            $this->repository->existsByStudentNumber(
                $data['student_number'],
                $student->id
            )
        ) {
            throw ValidationException::withMessages([
                'student_number' => 'Nomor siswa sudah digunakan.',
            ]);
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool)$data['is_active'];
        }

        DB::transaction(function () use (
            $student,
            $data
        ) {
            unset($data['user_id']);

            $this->repository->update(
                $student,
                $data
            );

            if ($student->user) {
                $this->accountService->updateAccount(
                    $student->user,
                    $data['full_name']
                );
            }
        });

        return $student
            ->refresh()
            ->load('classRoom', 'user');
    }

    public function delete(Student $student): bool
    {
        return DB::transaction(
            fn() => $this->repository->delete($student)
        );
    }

    public function updateStatus(
        Student $student,
        bool    $isActive
    ): Student
    {
        $this->repository->update(
            $student,
            [
                'is_active' => $isActive,
            ]
        );

        return $student->refresh()->load('classRoom');
    }
}
