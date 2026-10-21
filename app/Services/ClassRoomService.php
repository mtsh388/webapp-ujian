<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Repositories\ClassRoomRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassRoomService
{
    public function __construct(
        protected ClassRoomRepository $repository
    ) {
    }

    public function datatable(Request $request)
    {
        return $this->repository->datatable($request);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['code'] = $this->generateCode(
                $data['education_level'],
                (int) $data['grade_level']
            );

            return $this->repository->create($data);
        });
    }

    protected function generateCode(string $educationLevel, int $gradeLevel): string
    {
        $prefix = strtoupper($educationLevel) . '-' . $gradeLevel;
        $codes = ClassRoom::withTrashed()
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code');

        $lastNumber = 0;

        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/i', (string) $code, $matches)) {
                $lastNumber = max($lastNumber, (int) $matches[1]);
            }
        }

        do {
            $lastNumber++;
            $code = $prefix . '-' . str_pad((string) $lastNumber, 3, '0', STR_PAD_LEFT);
        } while (ClassRoom::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function update(ClassRoom $classRoom, array $data)
    {
        unset($data['code']);

        return $this->repository->update($classRoom, $data);
    }

    public function updateStatus(ClassRoom $classRoom, bool $isActive)
    {
        return $this->repository->updateStatus($classRoom, $isActive);
    }

    public function delete(ClassRoom $classRoom)
    {
        return $this->repository->delete($classRoom);
    }
}
