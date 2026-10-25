<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $service
    )
    {
    }

    public function index(): View
    {
        return view('students.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getDataTable($request)
        );
    }

    public function create(): View
    {
        $classes = ClassRoom::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'students.create',
            compact('classes')
        );
    }

    public function store(
        StoreStudentRequest $request
    ): RedirectResponse
    {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('students.index')
            ->with(
                'success',
                'Data siswa berhasil ditambahkan.'
            );
    }

    public function show(Student $student): View
    {
        $student->load('classRoom', 'user');

        return view(
            'students.show',
            compact('student')
        );
    }

    public function edit(Student $student): View
    {
        $student->load('classRoom', 'user');

        $classes = ClassRoom::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'students.edit',
            compact('student', 'classes')
        );
    }

    public function update(
        UpdateStudentRequest $request,
        Student              $student
    ): RedirectResponse
    {
        $this->service->update(
            $student,
            $request->validated()
        );

        return redirect()
            ->route('students.index')
            ->with(
                'success',
                'Data siswa berhasil diperbarui.'
            );
    }

    public function destroy(Student $student): JsonResponse
    {
        $this->service->delete($student);

        return response()->json([
            'status' => true,
            'message' => 'Data siswa berhasil dihapus.',
        ]);
    }

    public function updateStatus(
        Request $request,
        Student $student
    ): JsonResponse
    {
        $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $student = $this->service->updateStatus(
            $student,
            $request->boolean('is_active')
        );

        return response()->json([
            'status' => true,
            'message' => $student->is_active
                ? 'Siswa berhasil diaktifkan.'
                : 'Siswa berhasil dinonaktifkan.',
            'is_active' => $student->is_active,
        ]);
    }

    public function updateAccountStatus(
        Request $request,
        Student $student
    ): JsonResponse {
        $request->validate([
            'account_is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $student = $this->service->updateAccountStatus(
            $student,
            $request->boolean('account_is_active')
        );

        return response()->json([
            'status' => true,
            'message' => $student->user->is_active
                ? 'Akun siswa berhasil diaktifkan. Status siswa tidak berubah otomatis.'
                : 'Akun siswa berhasil dinonaktifkan dan status siswa diubah menjadi Tidak Aktif.',
            'account_is_active' => (bool) $student->user->is_active,
            'is_active' => $student->is_active,
        ]);
    }
}
