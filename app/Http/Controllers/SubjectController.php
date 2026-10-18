<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(
        protected SubjectService $service
    )
    {
    }

    public function index(): View
    {
        return view('subjects.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getDataTable(
                $request
            )
        );
    }

    public function create(): View
    {
        $classes = ClassRoom::query()->where('is_active', true)->orderBy('education_level')->orderBy('grade_level')->get();

        return view('subjects.create', compact('classes'));
    }

    public function store(
        StoreSubjectRequest $request
    ): RedirectResponse
    {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('subjects.index')
            ->with(
                'success',
                'Mata pelajaran berhasil ditambahkan.'
            );
    }

    public function show(Subject $subject): View
    {
        $subject->load('classRoom');

        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject): View
    {
        $classes = ClassRoom::query()->where('is_active', true)->orderBy('education_level')->orderBy('grade_level')->get();
        $subject->load('classRoom');

        return view('subjects.edit', compact('subject', 'classes'));
    }

    public function update(
        UpdateSubjectRequest $request,
        Subject              $subject
    ): RedirectResponse
    {
        $this->service->update(
            $subject,
            $request->validated()
        );

        return redirect()
            ->route('subjects.index')
            ->with(
                'success',
                'Mata pelajaran berhasil diperbarui.'
            );
    }

    public function destroy(
        Subject $subject
    ): JsonResponse
    {
        $this->service->delete($subject);

        return response()->json([
            'status' => true,
            'message' => 'Mata pelajaran berhasil dihapus.',
        ]);
    }

    public function updateStatus(
        Request $request,
        Subject $subject
    ): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $subject = $this->service->updateStatus(
            $subject,
            $validated['is_active']
        );

        return response()->json([
            'status' => true,
            'message' => $subject->is_active
                ? 'Mata pelajaran berhasil diaktifkan.'
                : 'Mata pelajaran berhasil dinonaktifkan.',
            'is_active' => $subject->is_active,
        ]);
    }
}
