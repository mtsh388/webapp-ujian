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

        $baseQuery = DB::table('exams as e')
            ->whereNull('e.deleted_at')
            ->where(function ($query) {
                $query->where('e.show_result_immediately', true)
                    ->orWhere('e.status', 'closed');
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('exam_attempts as ea')
                    ->whereColumn('ea.exam_id', 'e.id')
                    ->whereIn('ea.status', ['submitted', 'expired'])
                    ->whereNotNull('ea.submitted_at');
            });

        $recordsTotal = (clone $baseQuery)->count('e.id');
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('e.title', 'like', '%' . $search . '%')
                    ->orWhere('e.code', 'like', '%' . $search . '%')
                    ->orWhereExists(function ($subjectQuery) use ($search) {
                        $subjectQuery->selectRaw('1')
                            ->from('subjects as search_subject')
                            ->whereColumn('search_subject.id', 'e.subject_id')
                            ->where('search_subject.name', 'like', '%' . $search . '%');
                    });
            });
        }

        $recordsFiltered = (clone $baseQuery)->count('e.id');

        $orderColumns = [
            1 => 'e.code',
            2 => 'e.title',
            3 => 'subjects.name',
            4 => 'completed_attempts',
            5 => 'latest_submitted_at',
        ];

        $orderIndex = (int) $request->input('order.0.column', 5);
        $orderColumn = $orderColumns[$orderIndex] ?? 'latest_submitted_at';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $rows = DB::table('exams as e')
            ->leftJoin('subjects', 'subjects.id', '=', 'e.subject_id')
            ->join('exam_attempts as ea', 'ea.exam_id', '=', 'e.id')
            ->whereNull('e.deleted_at')
            ->whereIn('ea.status', ['submitted', 'expired'])
            ->whereNotNull('ea.submitted_at')
            ->where(function ($query) {
                $query->where('e.show_result_immediately', true)
                    ->orWhere('e.status', 'closed');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('e.title', 'like', '%' . $search . '%')
                        ->orWhere('e.code', 'like', '%' . $search . '%')
                        ->orWhere('subjects.name', 'like', '%' . $search . '%');
                });
            })
            ->select([
                'e.id',
                'e.code',
                'e.title',
                'subjects.name as subject_name',
                'e.passing_score',
            ])
            ->selectRaw('COUNT(ea.id) as completed_attempts')
            ->selectRaw('MAX(ea.submitted_at) as latest_submitted_at')
            ->groupBy('e.id', 'e.code', 'e.title', 'subjects.name', 'e.passing_score')
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('e.id', 'desc')
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($row, $index) use ($start) {
                return [
                    'no' => $start + $index + 1,
                    'id' => $row->id,
                    'exam_code' => $row->code,
                    'exam_title' => $row->title,
                    'subject_name' => $row->subject_name,
                    'passing_score' => $row->passing_score === null ? null : (float) $row->passing_score,
                    'completed_attempts' => (int) $row->completed_attempts,
                    'latest_submitted_at' => $row->latest_submitted_at,
                    'ranking_url' => route('results.ranking', $row->id),
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
            $rankedAttempts->where(function ($query) use ($search, $isRankingManager) {
                $query->where('student_name', 'like', '%' . $search . '%')
                    ->orWhere('class_name', 'like', '%' . $search . '%');

                if ($isRankingManager) {
                    $query->orWhere('student_number', 'like', '%' . $search . '%');
                }
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
                    'student_name' => $row->student_name,
                    'class_name' => $row->class_name,
                    'score' => (float) $row->score,
                    'duration_seconds' => $row->duration_seconds === null ? null : (int) $row->duration_seconds,
                    'status' => $row->status,
                    'passed' => $row->passed === null ? null : (bool) $row->passed,
                    'submitted_at' => $row->submitted_at,
                    'is_current_student' => $studentId !== null && (int) $row->student_id === $studentId,
                ];

                if ($isRankingManager) {
                    $data['id'] = $row->id;
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
            ->leftJoin('users as su', 'su.id', '=', 's.user_id')
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
                DB::raw("CASE WHEN NULLIF(TRIM(s.full_name), '') IS NULL OR s.full_name = s.student_number THEN COALESCE(NULLIF(TRIM(su.name), ''), s.full_name, s.student_number) ELSE s.full_name END as student_name"),
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

        $query = DB::table('exam_attempts as ea')
            ->join('students as s', 's.id', '=', 'ea.student_id')
            ->join('student_parent as sp', 'sp.student_id', '=', 's.id')
            ->join('exams as e', 'e.id', '=', 'ea.exam_id')
            ->where('sp.parent_id', $parent->id)
            ->whereIn('ea.status', ['submitted', 'expired'])
            ->whereNotNull('ea.submitted_at')
            ->where(function ($builder) {
                $builder->where('e.show_result_immediately', true)
                    ->orWhere('e.status', 'closed');
            });

        $totalAttempts = (clone $query)->count('ea.id');

        return view('results.children', [
            'totalAttempts' => $totalAttempts,
            'totalFinished' => $totalAttempts,
        ]);
    }

    public function childrenResultsData(Request $request): JsonResponse
    {
        $parent = \App\Models\ParentModel::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $query = DB::table('exam_attempts as ea')
            ->join('students as s', 's.id', '=', 'ea.student_id')
            ->join('student_parent as sp', 'sp.student_id', '=', 's.id')
            ->join('exams as e', 'e.id', '=', 'ea.exam_id')
            ->where('sp.parent_id', $parent->id)
            ->whereIn('ea.status', ['submitted', 'expired'])
            ->whereNotNull('ea.submitted_at')
            ->where(function ($builder) {
                $builder->where('e.show_result_immediately', true)
                    ->orWhere('e.status', 'closed');
            });

        $recordsTotal = (clone $query)->count('ea.id');
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('s.full_name', 'like', '%' . $search . '%')
                    ->orWhere('s.student_number', 'like', '%' . $search . '%')
                    ->orWhere('e.title', 'like', '%' . $search . '%')
;
            });
        }

        $recordsFiltered = (clone $query)->count('ea.id');
        $orderColumns = [
            1 => 's.full_name',
            2 => 'e.title',
            3 => 'ea.started_at',
            4 => 'ea.submitted_at',
            5 => 'ea.score',
            6 => 'ea.status',
        ];
        $orderIndex = (int) $request->input('order.0.column', 4);
        $orderColumn = $orderColumns[$orderIndex] ?? 'ea.submitted_at';
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $rows = $query->select([
                'ea.id',
                'ea.started_at',
                'ea.submitted_at',
                'ea.score',
                'ea.status',
                's.full_name as student_name',
                's.student_number',
                'e.title as exam_title',
            ])
            ->orderBy($orderColumn, $direction)
            ->orderByDesc('ea.id')
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($row, $index) use ($start) {
                $finished = $row->submitted_at !== null;

                return [
                    'no' => $start + $index + 1,
                    'id' => $row->id,
                    'student_name' => $row->student_name,
                    'student_number' => $row->student_number,
                    'exam_title' => $row->exam_title ?: '-',
                    'started_at' => $row->started_at,
                    'submitted_at' => $row->submitted_at,
                    'score' => $finished ? (float) ($row->score ?? 0) : null,
                    'status' => $finished ? 'Selesai' : 'Belum Selesai',
                    'detail_url' => $finished ? route('results.children.show', $row->id) : null,
                ];
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
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
