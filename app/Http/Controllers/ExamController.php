<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService
    )
    {
    }

    public function index()
    {
        return view('exams.index');
    }

    public function questions($id)
    {
        $data = $this->examService->getExamWithQuestions($id);

        return view('exams.questions', [
            'exam' => $data['exam'],
            'questions' => $data['questions'],
            'availableQuestions' => $data['availableQuestions'],
        ]);
    }

    public function storeQuestions(Request $request, $id)
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:question_banks,id'],
        ], [
            'question_ids.required' => 'Pilih setidaknya satu soal untuk ditambahkan.',
            'question_ids.min' => 'Pilih setidaknya satu soal untuk ditambahkan.',
            'question_ids.*.integer' => 'ID soal tidak valid.',
            'question_ids.*.exists' => 'Salah satu soal yang dipilih tidak ditemukan.',
        ]);

        try {
            $this->examService->addQuestionsToExam(
                (int)$id,
                $validated['question_ids']
            );

            return redirect()
                ->back()
                ->with('success', 'Soal berhasil ditambahkan ke ujian.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', 'Gagal menambahkan soal: ' . $e->getMessage());
        }
    }

    public function destroyQuestion($id, $questionId)
    {
        try {
            $this->examService->removeQuestionFromExam(
                (int)$questionId
            );

            return response()->json([
                'status' => true,
                'message' => 'Soal berhasil dihapus dari ujian.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus soal.',
            ], 500);
        }
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $this->examService->getDataTable($request)
        );
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $exam->delete();

        return response()->json([
            'status' => true,
            'message' => 'Ujian berhasil dihapus.',
        ]);
    }

    public function create()
    {
        $subjects = $this->examService->getSubjects();

        return view('exams.create', compact('subjects'));
    }

    public function store(StoreExamRequest $request)
    {
        try {
            $this->examService->create(
                $request->validated()
            );

            return redirect()
                ->route('exams.index')
                ->with('success', 'Ujian berhasil dibuat dan disimpan.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal membuat ujian: ' . $e->getMessage()
                );
        }
    }

    public function available(Request $request)
    {
        $exams = $this->examService->getAvailableForStudent(
            auth()->id(),
            9
        );

        return view('exams.available', compact('exams'));
    }

    public function participants(Exam $exam)
    {
        $data = $this->examService->getExamParticipants(
            $exam->id
        );

        return view('exams.participants', [
            'exam' => $data['exam'],
            'participants' => $data['participants'],
            'availableStudents' => $data['availableStudents'],
        ]);
    }

    public function storeParticipants(
        Request $request,
        Exam    $exam
    )
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => [
                'integer',
                'exists:students,id',
            ],
        ], [
            'student_ids.required' => 'Pilih setidaknya satu siswa.',
            'student_ids.min' => 'Pilih setidaknya satu siswa.',
            'student_ids.*.integer' => 'ID siswa tidak valid.',
            'student_ids.*.exists' => 'Salah satu siswa tidak ditemukan.',
        ]);

        try {
            $this->examService->addParticipants(
                $exam->id,
                $validated['student_ids']
            );

            return redirect()
                ->route('exams.participants', $exam)
                ->with(
                    'success',
                    'Peserta berhasil ditambahkan ke ujian.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Gagal menambahkan peserta: ' . $e->getMessage()
                );
        }
    }

    public function updateParticipantStatus(
        Exam            $exam,
        ExamParticipant $participant
    ): JsonResponse
    {
        try {
            $status = $this->examService->toggleParticipantStatus(
                $exam->id,
                $participant->id
            );

            return response()->json([
                'status' => true,
                'is_active' => $status,
                'message' => $status
                    ? 'Peserta berhasil diaktifkan.'
                    : 'Peserta berhasil dinonaktifkan.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Gagal mengubah status peserta.',
            ], 500);
        }
    }

    public function destroyParticipant(
        Exam            $exam,
        ExamParticipant $participant
    ): JsonResponse
    {
        try {
            $this->examService->removeParticipant(
                $exam->id,
                $participant->id
            );

            return response()->json([
                'status' => true,
                'message' => 'Peserta berhasil dihapus dari ujian.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus peserta.',
            ], 500);
        }
    }

    public function edit(Exam $exam)
    {
        $subjects = $this->examService->getSubjects();

        return view('exams.edit', [
            'exam' => $exam,
            'subjects' => $subjects,
        ]);
    }

    public function update(
        StoreExamRequest $request,
        Exam             $exam
    )
    {
        try {
            $this->examService->update(
                $exam,
                $request->validated()
            );

            return redirect()
                ->route('exams.index')
                ->with(
                    'success',
                    'Ujian berhasil diperbarui.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui ujian: ' . $e->getMessage()
                );
        }
    }

}
