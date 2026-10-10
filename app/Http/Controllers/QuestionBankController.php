<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkQuestionImportRequest;
use App\Http\Requests\StoreQuestionBankRequest;
use App\Http\Requests\UpdateQuestionBankRequest;
use App\Models\QuestionBank;
use App\Models\QuestionMedia;
use App\Models\QuestionOptionMedia;
use App\Services\BulkQuestionImportService;
use App\Services\QuestionBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function __construct(
        protected QuestionBankService       $service,
        protected BulkQuestionImportService $bulkImportService
    )
    {
    }

    public function index(): View
    {
        $subjects = $this->service->getSubjects();

        return view(
            'question-banks.index',
            compact('subjects')
        );
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getDataTable($request)
        );
    }

    public function create(): View
    {
        $subjects = $this->service->getSubjects();

        return view(
            'question-banks.create',
            compact('subjects')
        );
    }

    public function store(
        StoreQuestionBankRequest $request
    ): RedirectResponse
    {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('questions.index')
            ->with(
                'success',
                'Bank soal berhasil ditambahkan.'
            );
    }

    public function import(): View
    {
        $subjects = $this->service->getSubjects();

        return view(
            'question-banks.import',
            compact('subjects')
        );
    }

    public function importStore(
        BulkQuestionImportRequest $request
    ): RedirectResponse
    {
        try {
            $result = $this->bulkImportService->import(
                file: $request->file('file'),
                subjectId: $request->integer('subject_id'),
                difficulty: $request
                    ->string('difficulty')
                    ->toString(),
                defaultScore: $request->float('default_score')
            );

            return redirect()
                ->route('questions.index')
                ->with(
                    'success',
                    "{$result['imported']} soal berhasil diimport. " .
                    "Pilihan Ganda: {$result['multiple_choice']}, " .
                    "Benar/Salah: {$result['true_false']}."
                );
        } catch (\Throwable $e) {
            Log::error(
                'Bulk question import failed.',
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function show(
        QuestionBank $question
    ): View
    {
        $questionBank = $this->service->find(
            $question->id
        );

        return view(
            'question-banks.show',
            compact('questionBank')
        );
    }

    public function edit(
        QuestionBank $question
    ): View
    {
        $questionBank = $this->service->find(
            $question->id
        );

        $subjects = $this->service->getSubjects();

        return view(
            'question-banks.edit',
            compact(
                'questionBank',
                'subjects'
            )
        );
    }

    public function update(
        UpdateQuestionBankRequest $request,
        QuestionBank              $question
    ): RedirectResponse
    {
        $this->service->update(
            $question,
            $request->validated()
        );

        return redirect()
            ->route('questions.index')
            ->with(
                'success',
                'Bank soal berhasil diperbarui.'
            );
    }

    public function destroy(
        QuestionBank $question
    ): RedirectResponse
    {
        $this->service->delete($question);

        return redirect()
            ->route('questions.index')
            ->with(
                'success',
                'Bank soal berhasil dihapus.'
            );
    }

    public function destroyMedia(
        QuestionMedia $media
    ): RedirectResponse
    {
        $questionBankId = $media->question_bank_id;

        $this->service->deleteQuestionMedia(
            $media
        );

        return redirect()
            ->route(
                'questions.edit',
                $questionBankId
            )
            ->with(
                'success',
                'Media soal berhasil dihapus.'
            );
    }

    public function destroyOptionMedia(
        QuestionOptionMedia $media
    ): RedirectResponse
    {
        $option = $media->option;

        $this->service->deleteOptionMedia(
            $media
        );

        return redirect()
            ->route(
                'questions.edit',
                $option->question_bank_id
            )
            ->with(
                'success',
                'Media pilihan jawaban berhasil dihapus.'
            );
    }
}
