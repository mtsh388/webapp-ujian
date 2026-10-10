<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParentRequest;
use App\Http\Requests\UpdateParentRequest;
use App\Models\ParentModel;
use App\Models\Student;
use App\Services\ParentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function __construct(
        protected ParentService $service
    )
    {
    }

    public function index(): View
    {
        return view('parents.index');
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getDataTable($request)
        );
    }

    public function create(): View
    {
        $students = Student::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view(
            'parents.create',
            compact('students')
        );
    }

    public function store(
        StoreParentRequest $request
    ): RedirectResponse
    {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('parents.index')
            ->with(
                'success',
                'Data orang tua berhasil ditambahkan.'
            );
    }

    public function show(
        ParentModel $parent
    ): View
    {
        $parent = $this->service->getDetail(
            $parent
        );

        return view(
            'parents.show',
            compact('parent')
        );
    }

    public function edit(
        ParentModel $parent
    ): View
    {
        $parent->load('students');

        $students = Student::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view(
            'parents.edit',
            compact(
                'parent',
                'students'
            )
        );
    }

    public function update(
        UpdateParentRequest $request,
        ParentModel         $parent
    ): RedirectResponse
    {
        $this->service->update(
            $parent,
            $request->validated()
        );

        return redirect()
            ->route('parents.index')
            ->with(
                'success',
                'Data orang tua berhasil diperbarui.'
            );
    }

    public function destroy(
        ParentModel $parent
    ): JsonResponse
    {
        $this->service->delete($parent);

        return response()->json([
            'status' => true,
            'message' => 'Data orang tua berhasil dihapus.',
        ]);
    }

    public function updateStatus(
        Request     $request,
        ParentModel $parent
    ): JsonResponse
    {
        $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $parent = $this->service->updateStatus(
            $parent,
            $request->boolean('is_active')
        );

        return response()->json([
            'status' => true,
            'message' => $parent->is_active
                ? 'Orang tua berhasil diaktifkan.'
                : 'Orang tua berhasil dinonaktifkan.',
            'is_active' => $parent->is_active,
        ]);
    }
}
