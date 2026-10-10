<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRoomRequest;
use App\Http\Requests\UpdateClassRoomRequest;
use App\Models\ClassRoom;
use App\Services\ClassRoomService;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{

    public function __construct(
        protected ClassRoomService $service
    )
    {
    }


    public function index()
    {
        return view('classes.index');
    }


    public function data(Request $request)
    {
        $result = $this->service
            ->datatable($request);


        return response()->json([

            'draw' =>
                $request->input('draw'),

            'recordsTotal' =>
                $result['recordsTotal'],

            'recordsFiltered' =>
                $result['recordsFiltered'],

            'data' =>
                $result['data'],

        ]);
    }


    public function create()
    {
        return view('classes.create');
    }


    public function store(
        StoreClassRoomRequest $request
    )
    {

        $this->service->create(
            $request->validated()
        );


        return redirect()
            ->route('classes.index')
            ->with(
                'success',
                'Data kelas berhasil ditambahkan.'
            );

    }


    public function show(
        ClassRoom $classRoom
    )
    {

        $classRoom->loadCount(
            'students'
        );


        return view(
            'classes.show',
            compact('classRoom')
        );

    }


    public function edit(
        ClassRoom $classRoom
    )
    {

        return view(
            'classes.edit',
            compact('classRoom')
        );

    }

    public function updateStatus(Request $request, ClassRoom $classRoom)
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->service->updateStatus(
            $classRoom,
            $request->boolean('is_active')
        );

        return response()->json([
            'status' => true,
            'message' => $request->boolean('is_active')
                ? 'Kelas berhasil diaktifkan.'
                : 'Kelas berhasil dinonaktifkan.',
            'is_active' => $request->boolean('is_active'),
        ]);
    }


    public function update(
        UpdateClassRoomRequest $request,
        ClassRoom              $classRoom
    )
    {


        $this->service->update(
            $classRoom,
            $request->validated()
        );


        return redirect()
            ->route('classes.index')
            ->with(
                'success',
                'Data kelas berhasil diperbarui.'
            );

    }


    public function destroy(
        ClassRoom $classRoom
    )
    {


        if (!$this->service->delete($classRoom)) {

            return redirect()
                ->route('classes.index')
                ->with(
                    'error',
                    'Kelas tidak dapat dihapus karena masih memiliki siswa.'
                );

        }


        return redirect()
            ->route('classes.index')
            ->with(
                'success',
                'Data kelas berhasil dihapus.'
            );

    }

}
