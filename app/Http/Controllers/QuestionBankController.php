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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function importTemplate(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'question-bank-template-');

        if ($path === false) {
            abort(500, 'Template import tidak dapat dibuat.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            abort(500, 'Template import tidak dapat dibuat.');
        }

        $paragraphs = [
            'SOAL 1 [PILIHAN_GANDA]',
            'Hasil dari 2 pangkat 3 adalah ...',
            'A. 6',
            'B. 8',
            'C. 9',
            'D. 12',
            'JAWABAN: B',
            'PEMBAHASAN:',
            'Karena 2 × 2 × 2 = 8.',
            '',
            'SOAL 2 [BENAR_SALAH]',
            'Indonesia berada di Asia Tenggara.',
            'JAWABAN: A',
            'PEMBAHASAN:',
            'Pernyataan tersebut benar.',
        ];

        $body = '';

        foreach ($paragraphs as $paragraph) {
            $text = htmlspecialchars($paragraph, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $preserve = $paragraph !== trim($paragraph) ? ' xml:space="preserve"' : '';
            $body .= '<w:p><w:r><w:t' . $preserve . '>' . $text . '</w:t></w:r></w:p>';
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $body
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');

        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');

        $zip->close();

        return response()
            ->download($path, 'template-import-bank-soal.docx')
            ->deleteFileAfterSend(true);
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
