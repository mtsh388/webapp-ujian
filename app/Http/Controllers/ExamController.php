<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamAttempt;
use App\Models\Student;
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

    public function questionsData(Request $request, Exam $exam): JsonResponse
    {
        $query = \App\Models\ExamQuestion::query()->where('exam_id', $exam->id);
        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('question', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [1 => 'type', 2 => 'question', 3 => 'score', 4 => 'sort_order'];
        $orderColumn = $orderColumns[(int) $request->input('order.0.column', 4)] ?? 'sort_order';
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $data = $query->orderBy($orderColumn, $direction)->orderBy('id')
            ->skip($start)->take($length)->get()
            ->values()->map(fn ($question, $index) => [
                'no' => $start + $index + 1,
                'id' => $question->id,
                'type' => $question->type,
                'question' => strip_tags(\Illuminate\Support\Str::limit($question->question, 120)),
                'score' => $question->score,
                'can_update' => auth()->user()->can('exam.update'),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function availableQuestionsData(Request $request, Exam $exam): JsonResponse
    {
        $attachedIds = \App\Models\ExamQuestion::query()
            ->where('exam_id', $exam->id)
            ->whereNotNull('question_bank_id')
            ->select('question_bank_id');

        $query = \App\Models\QuestionBank::query()
            ->where('subject_id', $exam->subject_id)
            ->where('is_active', true)
            ->whereNotIn('id', $attachedIds);

        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('question', 'like', '%' . $search . '%');
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [1 => 'code', 2 => 'type', 3 => 'question'];
        $orderColumn = $orderColumns[(int) $request->input('order.0.column', 1)] ?? 'code';
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $data = $query->orderBy($orderColumn, $direction)->orderBy('id')
            ->skip($start)->take($length)->get()
            ->values()->map(fn ($question, $index) => [
                'no' => $start + $index + 1,
                'id' => $question->id,
                'code' => $question->code,
                'type' => $question->type,
                'question' => strip_tags(\Illuminate\Support\Str::limit($question->question, 150)),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
                (int) $id,
                (int) $questionId
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

        $student = Student::query()
            ->where('user_id', auth()->id())
            ->first();

        $activeAttempts = $student
            ? ExamAttempt::query()
                ->where('student_id', $student->id)
                ->where('status', 'in_progress')
                ->get()
                ->keyBy('exam_id')
            : collect();

        return view('exams.available', compact('exams', 'activeAttempts'));
    }

    public function participants(Exam $exam)
    {
        $data = $this->examService->getExamParticipants($exam->id);

        return view('exams.participants', [
            'exam' => $data['exam'],
            'availableStudents' => $data['availableStudents'],
        ]);
    }

    public function participantsData(Request $request, Exam $exam): JsonResponse
    {
        $query = ExamParticipant::query()
            ->where('exam_id', $exam->id)
            ->with(['student.classRoom', 'student.user']);

        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->whereHas('student', function ($studentQuery) use ($search) {
                $studentQuery->where(function ($builder) use ($search) {
                    $builder->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('student_number', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', '%' . $search . '%'))
                        ->orWhereHas('classRoom', fn ($classQuery) => $classQuery->where('name', 'like', '%' . $search . '%'));
                });
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [
            1 => 'assigned_at',
            5 => 'is_active',
        ];
        $orderColumn = $orderColumns[(int) $request->input('order.0.column', 4)] ?? 'assigned_at';
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $data = $query->orderBy($orderColumn, $direction)
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get()
            ->values()
            ->map(function ($participant, $index) use ($start, $exam) {
                $student = $participant->student;

                return [
                    'no' => $start + $index + 1,
                    'id' => $participant->id,
                    'student_name' => $student?->full_name ?? '-',
                    'email' => $student?->user?->email ?? '-',
                    'student_number' => $student?->student_number ?? '-',
                    'class_name' => $student?->classRoom?->name ?? '-',
                    'assigned_at' => $participant->assigned_at?->format('Y-m-d H:i:s'),
                    'is_active' => (bool) $participant->is_active,
                    'can_update' => auth()->user()->can('exam.update'),
                    'status_url' => route('exams.participants.status', [$exam, $participant]),
                    'delete_url' => route('exams.participants.destroy', [$exam, $participant]),
                ];
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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

    public function publish(Exam $exam)
    {
        $exam->forceFill(['status' => 'published'])->save();
        $this->examService->syncAutoAssignedParticipants($exam->fresh('subject'));

        return redirect()
            ->route('exams.index')
            ->with('success', 'Ujian berhasil dipublikasikan.');
    }

    public function draft(Exam $exam)
    {
        $exam->forceFill(['status' => 'draft'])->save();

        return redirect()
            ->route('exams.index')
            ->with('success', 'Status ujian berhasil diubah menjadi Draft.');
    }

    public function close(Exam $exam)
    {
        $exam->forceFill(['status' => 'closed'])->save();

        return redirect()
            ->route('exams.index')
            ->with('success', 'Ujian berhasil ditutup.');
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
