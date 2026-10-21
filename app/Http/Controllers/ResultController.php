<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(
        protected ExamAttemptService $service
    )
    {
    }

    public function index(): View
    {
        abort_unless(auth()->user()->can('result.view'), 403);

        $attempts = ExamAttempt::query()
            ->with(['exam', 'student'])
            ->whereIn('status', ['submitted', 'expired'])
            ->whereNotNull('submitted_at')
            ->whereHas('exam', function ($query) {
                $query->where(function ($query) {
                    $query->where('show_result_immediately', true)
                        ->orWhere('status', 'closed');
                });
            })
            ->latest('submitted_at')
            ->paginate(15);

        return view('results.index', compact('attempts'));
    }

    public function ranking(Exam $exam): View
    {
        abort_unless(auth()->user()->can('result.view'), 403);

        abort_unless(
            $exam->show_result_immediately || $exam->status === 'closed',
            403,
            'Ranking belum tersedia untuk ujian ini.'
        );

        $attempts = ExamAttempt::query()
            ->with('student.classRoom')
            ->where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->whereNotNull('submitted_at')
            ->orderByDesc('score')
            ->orderByRaw('CASE WHEN duration_seconds IS NULL THEN 1 ELSE 0 END')
            ->orderBy('duration_seconds')
            ->orderBy('submitted_at')
            ->get()
            ->unique('student_id')
            ->values();

        $rank = 0;
        $position = 0;
        $previousScore = null;
        $previousDuration = null;

        foreach ($attempts as $attempt) {
            $position++;
            $score = (float) $attempt->score;
            $duration = (int) ($attempt->duration_seconds ?? PHP_INT_MAX);

            if ($previousScore === null || $score !== $previousScore || $duration !== $previousDuration) {
                $rank = $position;
            }

            $attempt->setAttribute('rank', $rank);
            $previousScore = $score;
            $previousDuration = $duration;
        }

        return view('results.ranking', compact('exam', 'attempts'));
    }

    public function show(
        ExamAttempt $attempt
    ): View|RedirectResponse
    {
        $this->ensureStudentCanViewAttempt($attempt);

        if (!in_array($attempt->status, [
            'submitted',
            'expired',
        ], true)) {
            return redirect()
                ->route('exams.attempt', [
                    'exam' => $attempt->exam_id,
                    'attempt' => $attempt->id,
                ])
                ->with(
                    'info',
                    'Ujian belum dikumpulkan.'
                );
        }

        $this->ensureResultIsVisible($attempt);

        $attempt->load([
            'exam.questions.options',
            'student',
            'answers.examQuestion',
            'answers.selectedOption',
        ]);

        return view(
            'results.show',
            compact('attempt')
        );
    }

    public function myResults(): View
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Akun ini tidak terhubung dengan data siswa.');
        }

        $attempts = ExamAttempt::query()
            ->with([
                'exam',
            ])
            ->where('student_id', $student->id)
            ->whereIn('status', [
                'submitted',
                'expired',
            ])
            ->whereHas('exam', function ($query) {
                $query->where(function ($query) {
                    $query->where('show_result_immediately', true)
                        ->orWhere('status', 'closed');
                });
            })
            ->latest('submitted_at')
            ->paginate(10);

        return view(
            'results.my-index',
            compact('attempts')
        );
    }

    public function myResult(
        ExamAttempt $attempt
    ): View|RedirectResponse
    {
        $this->ensureStudentCanViewAttempt($attempt);

        if (!in_array($attempt->status, [
            'submitted',
            'expired',
        ], true)) {
            return redirect()
                ->route('exams.attempt', [
                    'exam' => $attempt->exam_id,
                    'attempt' => $attempt->id,
                ])
                ->with(
                    'info',
                    'Ujian belum dikumpulkan.'
                );
        }

        $this->ensureResultIsVisible($attempt);

        $attempt->load([
            'exam.questions.options',
            'student',
            'answers.examQuestion',
            'answers.selectedOption',
        ]);

        return view(
            'results.my-show',
            compact('attempt')
        );
    }

    public function showMyResult(
        ExamAttempt $attempt
    ): View|RedirectResponse
    {
        return $this->myResult($attempt);
    }

    public function pdf(ExamAttempt $attempt)
    {
        $this->ensureStudentCanViewAttempt($attempt);

        if (!in_array($attempt->status, [
            'submitted',
            'expired',
        ], true)) {
            return redirect()
                ->route('exams.attempt', [
                    'exam' => $attempt->exam_id,
                    'attempt' => $attempt->id,
                ])
                ->with(
                    'info',
                    'Ujian belum dikumpulkan.'
                );
        }

        $this->ensureResultIsVisible($attempt);

        $attempt->load([
            'exam.questions.options',
            'student',
            'answers.examQuestion',
            'answers.selectedOption',
        ]);

        $questions = $attempt->exam?->questions
            ?->sortBy('sort_order')
            ->values() ?? collect();

        $pdf = Pdf::loadView('results.pdf', [
            'attempt' => $attempt,
            'questions' => $questions,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download(
            'hasil-ujian-' .
            ($attempt->exam?->code ?? $attempt->id) .
            '.pdf'
        );
    }

    private function ensureResultIsVisible(ExamAttempt $attempt): void
    {
        $attempt->loadMissing('exam');

        abort_unless(
            $attempt->exam &&
            ($attempt->exam->show_result_immediately || $attempt->exam->status === 'closed'),
            403,
            'Hasil ujian belum tersedia.'
        );
    }

    private function ensureStudentCanViewAttempt(
        ExamAttempt $attempt
    ): void
    {
        if (auth()->user()->can('result.view')) {
            return;
        }

        $student = auth()->user()->student;

        if (!$student) {
            abort(
                403,
                'Akun ini tidak terhubung dengan data siswa.'
            );
        }

        if ((int)$attempt->student_id !== (int)$student->id) {
            abort(403);
        }
    }

    public function childrenResults(): \Illuminate\View\View
    {
        $parent = \App\Models\ParentModel::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $students = $parent->students()
            ->with([
                'examAttempts.exam',
            ])
            ->get();

        $attempts = $students
            ->flatMap(function ($student) {
                return $student->examAttempts
                    ->whereIn('status', ['submitted', 'expired'])
                    ->filter(fn ($attempt) => $attempt->submitted_at !== null && ($attempt->exam?->show_result_immediately || $attempt->exam?->status === 'closed'))
                    ->map(function ($attempt) use ($student) {
                        $attempt->setRelation('student', $student);

                        return $attempt;
                    });
            })
            ->sortByDesc('submitted_at')
            ->values();

        return view('results.children', compact('attempts'));
    }

    public function childResult(\App\Models\ExamAttempt $attempt): \Illuminate\View\View
    {
        $parent = \App\Models\ParentModel::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $isChild = $parent->students()
            ->where('students.id', $attempt->student_id)
            ->exists();

        abort_unless($isChild, 403, 'Anda tidak memiliki akses ke hasil ujian ini.');

        abort_unless(
            in_array($attempt->status, ['submitted', 'expired'], true)
                && $attempt->submitted_at !== null,
            404
        );

        $this->ensureResultIsVisible($attempt);

        $attempt->load([
            'student.classRoom',
            'exam',
            'answers.examQuestion.options',
            'answers.selectedOption',
        ]);

        return view('results.children-show', compact('attempt'));
    }
}
