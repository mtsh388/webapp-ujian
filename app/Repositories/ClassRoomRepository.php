<?php

namespace App\Repositories;

use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ClassRoomRepository
{

    public function datatable(Request $request)
    {
        $query = ClassRoom::query()
            ->withCount('students');


        $recordsTotal = $query->count();


        $search = trim(
            $request->input('search.value', '')
        );


        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('education_level', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhere('registration_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");

            });

        }


        $recordsFiltered = $query->count();


        $columns = [
            'id',
            'name',
            'education_level',
            'grade_level',
            'registration_type',
            'code',
            'students_count',
            'description',
            'is_active',
            'id',
        ];


        $columnIndex = $request->input(
            'order.0.column',
            1
        );


        $orderColumn = $columns[$columnIndex]
            ?? 'name';


        $orderDirection = $request->input(
            'order.0.dir',
            'asc'
        );


        $query->orderBy(
            $orderColumn,
            $orderDirection
        );


        $length = $request->input(
            'length',
            10
        );


        if ($length != -1) {

            $query
                ->offset(
                    $request->input('start', 0)
                )
                ->limit($length);

        }


        $data = $query->get()
            ->map(function ($item, $index) use ($request) {

                return [
                    'id' => $item->id,

                    'no' =>
                        $request->input('start', 0)
                        +
                        $index
                        +
                        1,

                    'name' => $item->name,

                    'education_level' => $item->education_level,

                    'grade_level' => $item->grade_level,

                    'registration_type' => $item->registration_type,

                    'code' => $item->code,

                    'students_count' =>
                        $item->students_count,

                    'description' =>
                        $item->description,

                    'is_active' =>
                        $item->is_active,
                ];

            });


        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }


    public function create(array $data)
    {
        return ClassRoom::create($data);
    }


    public function update(
        ClassRoom $classRoom,
        array     $data
    )
    {

        return $classRoom->update($data);

    }

    public function updateStatus(ClassRoom $classRoom, bool $isActive)
    {
        return $classRoom->update(['is_active' => $isActive,]);
    }

    public function delete(
        ClassRoom $classRoom
    )
    {

        if ($classRoom->students()->exists()) {
            return false;
        }


        return $classRoom->delete();

    }

}
