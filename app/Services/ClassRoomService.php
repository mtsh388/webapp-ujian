<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Repositories\ClassRoomRepository;
use Illuminate\Http\Request;

class ClassRoomService
{

    public function __construct(
        protected ClassRoomRepository $repository
    )
    {
    }


    public function datatable(Request $request)
    {
        return $this->repository->datatable($request);
    }


    public function create(array $data)
    {
        return $this->repository->create($data);
    }


    public function update(
        ClassRoom $classRoom,
        array     $data
    )
    {
        return $this->repository->update(
            $classRoom,
            $data
        );
    }

    public function updateStatus(ClassRoom $classRoom, bool $isActive)
    {
        return $this->repository->updateStatus($classRoom, $isActive);
    }


    public function delete(
        ClassRoom $classRoom
    )
    {
        return $this->repository->delete(
            $classRoom
        );
    }

}
