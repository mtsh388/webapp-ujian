<?php

namespace App\\Http\\Controllers;

use App\\Models\\Exam;
use App\\Models\\ExamAttempt;
use App\\Models\\ExamViolation;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

class ExamViolationController extends Controller
{
    public function index(): View
    {
        return view('proctoring.index', [
            'totalToday' => ExamViolation::whereDate('created_at', today())->count(),
            'attemptsFlagged' => ExamViolation::whereDate('created_at', today())
                ->distinct('exam_attempt_id')
                ->count('exam_attempt_id'),
            'activeAttemptsFlagged' => ExamViolation::whereHas('attempt', function ($query) {
                $query->where('status', 'in_progress');
            })->distinct('exam_attempt_id')->count('exam_attempt_id'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? 100 : min(max($length, 1), 100);

        $baseQuery = ExamViolation::query()
            ->leftJoin('exam_attempts as attempts', 'attempts.id', '=', 'exam_violations.exam_attempt_id')
            ->leftJoin('students', 'students.id', '=', 'attempts.student_id')
            ->leftJoin('exams', 'exams.id', '=', 'attempts.exam_id')
            ->select('exam_violations.*');

        $recordsTotal = (clone $baseQuery)->count('exam_violations.id');

        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('students.full_name', 'like', "%{$search}%")
                    ->orWhere('exams.title', 'like', "%{$search}%")
                    ->orWhere('exam_violations.event_type', 'like', "%{$search}%")
                    ->orWhere('exam_violations.exam_attempt_id', 'like', "%{$search}%")
                    ->orWhere('attempts.status', 'like', "%{$search}%")
                    ->orWhere('exam_violations.created_at', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $baseQuery)->count('exam_violations.id');

        $columns = [
            1 => 'exam_violations.created_at',
            2 => 'students.full_name',
            3 => 'exams.title',
            4 => 'exam_violations.event_type',
            5 => 'attempts.id',
            6 => 'attempts.status',
        ];

        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderBy = $columns[$orderColumn] ?? 'exam_violations.created_at';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $violations = $baseQuery
            ->with([
                'attempt.exam',
                'attempt.student',
                'attempt' => fn ($query) => $query->withCount('violations'),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('exam_violations.id', 'desc')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $violations->values()->map(function (ExamViolation $violation, int $index) use ($start) {
            $attempt = $violation->attempt;

            return [
                'no' => $start + $index + 1,
                'id' => $violation->id,
                'created_date' => $violation->created_at?->format('d-m-Y') ?? '-',
                'created_time' => $violation->created_at?->format('H:i:s') ?? '-',
                'student_name' => $attempt?->student?->full_name ?? 'Peserta tidak tersedia',
                'attempt_id' => $violation->exam_attempt_id,
                'exam_title' => $attempt?->exam?->title ?? 'Ujian tidak tersedia',
                'event_label' => $violation->event_label,
                'event_type' => $violation->event_type,
                'visibility_state' => $violation->metadata['visibility_state'] ?? null,
                'violations_count' => $attempt?->violations_count ?? 0,
                'status' => $attempt?->status,
                'status_label' => $attempt?->status
                    ? ucfirst(str_replace('_', ' ', $attempt->status))
                    : 'Tidak tersedia',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request, Exam $exam, ExamAttempt $attempt): JsonResponse
    {
        $student = $request->user()?->student;

        abort_unless(
            $student &&
            (int) $attempt->student_id === (int) $student->id &&
            (int) $attempt->exam_id === (int) $exam->id,
            403
        );

        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ujian sudah tidak aktif.',
            ], 422);
        }

        $validated = $request->validate([
            'event_type' => ['required', 'in:tab_hidden,fullscreen_exit'],
            'metadata' => ['nullable', 'array'],
            'metadata.visibility_state' => ['nullable', 'string', 'max:30'],
            'metadata.fullscreen_supported' => ['nullable', 'boolean'],
        ]);

        $violation = ExamViolation::create([
            'exam_attempt_id' => $attempt->id,
            'event_type' => $validated['event_type'],
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $count = ExamViolation::where('exam_attempt_id', $attempt->id)->count();

        $warning = match (true) {
            $count === 1 => [
                'level' => 1,
                'title' => 'Pengingat aktivitas ujian',
                'message' => 'Aktivitas perpindahan tab atau fullscreen tercatat. Tetap fokus pada halaman ujian.',
            ],
            $count === 2 => [
                'level' => 2,
                'title' => 'Peringatan kedua',
                'message' => 'Aktivitas serupa kembali tercatat. Mohon tetap berada di halaman ujian.',
            ],
            default => [
                'level' => 3,
                'title' => 'Peringatan lanjutan',
                'message' => 'Aktivitas tambahan tercatat dan akan ditinjau pengawas. Ujian tidak dibatalkan otomatis.',
            ],
        };

        return response()->json([
            'success' => true,
            'event_id' => $violation->id,
            'event_count' => $count,
            'warning' => $warning,
        ], 201);
    }
}
