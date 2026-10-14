<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerExamRequest;
use App\Http\Requests\StartExamAttemptRequest;
use App\Http\Requests\SubmitExamRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ExamAttemptController extends Controller
{
    public function __construct(
        protected ExamAttemptService $service
    )
    {
    }

    public function start(
        StartExamAttemptRequest $request,
        Exam                    $exam
    ): RedirectResponse
    {
        try {
            $student = $this->getAuthenticatedStudent();

            $attempt = $this->service->start(
                $exam,
                $student
            );

            return redirect()->route('exams.attempt', [
                'exam' => $exam->id,
                'attempt' => $attempt->id,
            ]);
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show(
        Request     $request,
        Exam        $exam,
        ExamAttempt $attempt
    ): View|RedirectResponse
    {
        try {
            $this->authorizeAttempt(
                $exam,
                $attempt
            );

            if ($attempt->status !== 'in_progress') {
                return redirect()
                    ->route('results.my.show', $attempt->id);
            }

            if ($exam->status !== 'published') {
                $attempt = $this->service->submit($attempt);

                return redirect()
                    ->route('results.my.show', $attempt->id)
                    ->with('info', 'Ujian telah ditutup.');
            }

            if ($this->service->isExpired($attempt)) {
                $attempt = $this->service->submit(
                    $attempt,
                    true
                );

                return redirect()
                    ->route(
                        'results.my.show',
                        $attempt->id
                    )
                    ->with(
                        'info',
                        'Waktu ujian telah habis.'
                    );
            }

            $questionNumber = max(
                1,
                (int)$request->query(
                    'question',
                    1
                )
            );

            $data = $this->service
                ->getAttemptForPage(
                    $attempt,
                    $questionNumber
                );

            if ($request->expectsJson()) {
                $answeredQuestionIds = $data['answers']
                    ->filter(fn ($answer) => $answer->selected_option_id !== null)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                return response()->json([
                    'success' => true,
                    'html' => view('exams.partials.attempt-question', [
                        ...$data,
                        'exam' => $exam,
                    ])->render(),
                    'currentNumber' => $data['currentNumber'],
                    'totalQuestions' => $data['totalQuestions'],
                    'answeredQuestionIds' => $answeredQuestionIds,
                    'answeredCount' => $answeredQuestionIds->count(),
                ]);
            }

            return view(
                'exams.attempt',
                [
                    ...$data,
                    'exam' => $exam,
                ]
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('exams.available')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function answer(
        AnswerExamRequest $request,
        Exam              $exam,
        ExamAttempt       $attempt
    ): JsonResponse
    {
        try {
            $this->authorizeAttempt(
                $exam,
                $attempt
            );

            $answer = $this->service->answer(
                $attempt,
                (int)$request->validated(
                    'exam_question_id'
                ),
                $request->validated(
                    'selected_option_id'
                )
                    ? (int)$request->validated(
                    'selected_option_id'
                )
                    : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan.',
                'data' => [
                    'answer_id' => $answer->id,
                    'exam_question_id' => $answer->exam_question_id,
                    'selected_option_id' => $answer->selected_option_id,
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function submit(
        SubmitExamRequest $request,
        Exam              $exam,
        ExamAttempt       $attempt
    ): RedirectResponse
    {
        try {
            $this->authorizeAttempt(
                $exam,
                $attempt
            );

            $attempt = $this->service->submit(
                $attempt
            );

            return redirect()
                ->route(
                    'results.my.show',
                    $attempt->id
                )
                ->with(
                    'success',
                    'Ujian berhasil dikumpulkan.'
                );
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    protected function getAuthenticatedStudent(): Student
    {
        $student = auth()->user()->student;

        if (!$student) {
            throw new RuntimeException(
                'Akun ini tidak terhubung dengan data siswa.'
            );
        }

        return $student;
    }

    protected function authorizeAttempt(
        Exam        $exam,
        ExamAttempt $attempt
    ): void
    {
        $student = $this->getAuthenticatedStudent();

        if (
            (int) $attempt->exam_id !== (int) $exam->id ||
            (int) $attempt->student_id !== (int) $student->id
        ) {
            abort(403);
        }
    }
}
