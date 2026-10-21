<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('results.index');
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('result.view'), 403);

        $baseQuery = DB::table('exam_attempts as ea')
            ->join('exams as e', 'e.id', '=', 'ea.exam_id')
            ->leftJoin('students as s', 's.id', '=', 'ea.student_id')
            ->whereIn('ea.status', ['submitted', 'expired'])
            ->whereNotNull('ea.submitted_at')
            ->where(function ($query) {
                $query->where('e.show_result_immediately', true)
                    ->orWhere('e.status', 'closed');
            });

        $recordsTotal = (clone $baseQuery)->count('ea.id');
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('e.title', 'like', '%' . $search . '%')
                    ->orWhere('e.code', 'like', '%' . $search . '%')
                    ->orWhere('s.full_name', 'like', '%' . $search . '%')
                    ->orWhere('s.student_number', 'like', '%' . $search . '%');
            });
        }

        $recordsFiltered = (clone $baseQuery)->count('ea.id');

        $orderColumns = [
            1 => 'e.title',
            2 => 's.full_name',
            3 => 'ea.score',
            4 => 'ea.status',
            5 => 'ea.submitted_at',
        ];

        $orderIndex = (int) $request->input('order.0.column', 5);
        $orderColumn = $orderColumns[$orderIndex] ?? 'ea.submitted_at';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $rows = $baseQuery
            ->select([
                'ea.id',
                'ea.exam_id',
                'ea.score',
                'ea.status',
                'ea.passed',
                'ea.submitted_at',
                'e.title as exam_title',
                'e.code as exam_code',
                'e.passing_score',
                's.full_name as student_name',
            ])
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('ea.id', 'desc')
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($row, $index) use ($start) {
                return [
                    'no' => $start + $index + 1,
                    'id' => $row->id,
                    'exam_id' => $row->exam_id,
                    'exam_title' => $row->exam_title,
                    'exam_code' => $row->exam_code,
                    'student_name' => $row->student_name,
                    'score' => (float) $row->score,
                    'passing_score' => (float) $row->passing_score,
                    'status' => $row->status,
                    'passed' => (bool) $row->passed,
                    'submitted_at' => $row->submitted_at,
                    'show_url' => route('results.show', $row->id),
                    'ranking_url' => route('results.ranking', $row->exam_id),
                ];
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function ranking(Exam $exam): View
    {
        $studentId = $this->authorizeRankingAccess($exam);
        $rankedAttempts = $this->rankedAttemptsQuery($exam);
        $totalRankedStudents = (clone $rankedAttempts)->count();
        $myRanking = $studentId
            ? DB::query()->fromSub(clone $rankedAttempts, 'ranked')->where('student_id', $studentId)->first()
            : null;

        return view('results.ranking', [
            'exam' => $exam,
            'myRanking' => $myRanking,
            'totalRankedStudents' => $totalRankedStudents,
            'isRankingManager' => auth()->user()->can('result.view'),
        ]);
    }

    public function rankingData(Exam $exam, Request $request): JsonResponse
    {
        $studentId = $this->authorizeRankingAccess($exam);
        $isRankingManager = auth()->user()->can('result.view');
        $rankedAttempts = DB::query()
            ->fromSub($this->rankedAttemptsQuery($exam), 'ranked')
            ->select('ranked.*');

        $recordsTotal = (clone $rankedAttempts)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $rankedAttempts->where(function ($query) use ($search) {
                $query->where('student_name', 'like', '%' . $search . '%')
                    ->orWhere('student_number', 'like', '%' . $search . '%')
                    ->orWhere('class_name', 'like', '%' . $search . '%');
            });
        }

        $recordsFiltered = (clone $rankedAttempts)->count();

        $orderColumns = [
            1 => 'rank_position',
            2 => 'student_name',
            3 => 'class_name',
            4 => 'score',
            5 => 'duration_seconds',
            6 => 'status',
            7 => 'submitted_at',
        ];

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $orderColumns[$orderIndex] ?? 'rank_position';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $rows = $rankedAttempts
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('student_name')
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($row, $index) use ($start, $studentId, $isRankingManager) {
                $data = [
                    'no' => $start + $index + 1,
                    'rank' => (int) $row->rank_position,
                    'id' => $row->id,
                    'student_name' => $row->student_name,
                    'student_number' => $row->student_number,
                    'class_name' => $row->class_name,
                    'score' => (float) $row->score,
                    'duration_seconds' => $row->duration_seconds === null ? null : (int) $row->duration_seconds,
                    'status' => $row->status,
                    'passed' => (bool) $row->passed,
                    'submitted_at' => $row->submitted_at,
                    'is_current_student' => $studentId !== null && (int) $row->student_id === $studentId,
                ];

                if ($isRankingManager) {
                    $data['student_number'] = $row->student_number;
                    $data['show_url'] = route('results.show', $row->id);
                }

                return $data;
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    private function authorizeRankingAccess(Exam $exam): ?int
    {
        abort_unless(
            $exam->show_result_immediately || $exam->status === 'closed',
            403,
            'Ranking belum tersedia untuk ujian ini.'
        );

        $user = auth()->user();

        if ($user->can('result.view')) {
            return null;
        }

        abort_unless($user->can('result.view_own'), 403);

        $student = $user->student;

        abort_unless(
            $student !== null,
            403,
            'Akun ini tidak terhubung dengan data siswa.'
        );

        $hasResult = DB::table('exam_attempts')
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->whereNotNull('submitted_at')
            ->exists();

        abort_unless($hasResult, 403, 'Anda belum memiliki hasil untuk ujian ini.');

        return (int) $student->id;
    }

    private function rankedAttemptsQuery(Exam $exam)
    {
        $attempts = DB::table('exam_attempts as ea')
            ->join('students as s', 's.id', '=', 'ea.student_id')
            ->leftJoin('classes as cr', 'cr.id', '=', 's.class_id')
            ->where('ea.exam_id', $exam->id)
            ->whereIn('ea.status', ['submitted', 'expired'])
            ->whereNotNull('ea.submitted_at')
            ->select([
                'ea.id',
                'ea.student_id',
                'ea.exam_id',
                'ea.score',
                'ea.duration_seconds',
                'ea.status',
                'ea.passed',
                'ea.submitted_at',
                's.full_name as student_name',
                's.student_number',
                'cr.name as class_name',
            ])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY ea.student_id ORDER BY ea.score DESC, CASE WHEN ea.duration_seconds IS NULL THEN 1 ELSE 0 END ASC, ea.duration_seconds ASC, ea.submitted_at ASC, ea.id ASC) as attempt_choice');

        $bestAttempts = DB::query()
            ->fromSub($attempts, 'attempts')
            ->where('attempt_choice', 1);

        return DB::query()
            ->fromSub($bestAttempts, 'best_attempts')
            ->select('best_attempts.*')
            ->selectRaw('RANK() OVER (ORDER BY score DESC, CASE WHEN duration_seconds IS NULL THEN 1 ELSE 0 END ASC, duration_seconds ASC) as rank_position');
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
