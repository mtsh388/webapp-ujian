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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
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

    public function importPreview(
        BulkQuestionImportRequest $request
    ): View|RedirectResponse
    {
        $path = $request->file('file')->storeAs(
            'question-imports',
            Str::uuid() . '.docx',
            'local'
        );

        try {
            $preview = $this->bulkImportService->preview(
                $request->file('file')
            );

            $pending = [
                'token' => Str::random(64),
                'path' => $path,
                'subject_id' => $request->integer('subject_id'),
                'difficulty' => $request->string('difficulty')->toString(),
                'default_score' => $request->float('default_score'),
            ];

            session(['question_import.pending' => $pending]);

            $subjects = $this->service->getSubjects();
            $subject = $subjects->firstWhere('id', $pending['subject_id']);

            return view(
                'question-banks.import-preview',
                compact('preview', 'pending', 'subject')
            );
        } catch (\\Throwable $e) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('questions.import')
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function importConfirm(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $pending = session('question_import.pending');

        if (
            !is_array($pending) ||
            !isset($pending['token'], $pending['path']) ||
            !hash_equals((string) $pending['token'], (string) $request->input('token'))
        ) {
            return redirect()
                ->route('questions.import')
                ->with('error', 'Sesi preview import tidak valid atau sudah berakhir. Silakan upload ulang file.');
        }

        $disk = Storage::disk('local');
        $path = $pending['path'];

        if (!$disk->exists($path)) {
            session()->forget('question_import.pending');

            return redirect()
                ->route('questions.import')
                ->with('error', 'File sementara tidak ditemukan. Silakan upload ulang file.');
        }

        try {
            $file = new UploadedFile(
                $disk->path($path),
                basename($path),
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                null,
                true
            );

            $result = $this->bulkImportService->import(
                file: $file,
                subjectId: (int) $pending['subject_id'],
                difficulty: (string) $pending['difficulty'],
                defaultScore: (float) $pending['default_score']
            );

            return redirect()
                ->route('questions.index')
                ->with(
                    'success',
                    "{$result['imported']} soal berhasil diimport. Pilihan Ganda: {$result['multiple_choice']}, Benar/Salah: {$result['true_false']}."
                );
        } catch (\\Throwable $e) {
            Log::error('Bulk question import failed.', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('questions.import')
                ->with('error', 'Import gagal dan tidak ada soal yang ditambahkan. Silakan periksa file Word lalu coba lagi.');
        } finally {
            $disk->delete($path);
            session()->forget('question_import.pending');
        }
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
