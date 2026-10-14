<?php

namespace App\\Http\\Controllers;

use App\\Models\\Exam;
use App\\Models\\ExamAttempt;
use App\\Models\\ExamViolation;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
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
            'totalViolations' => ExamViolation::count(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $baseQuery = DB::table('exam_violations')
            ->join('exam_attempts', 'exam_attempts.id', '=', 'exam_violations.exam_attempt_id')
            ->leftJoin('students', 'students.id', '=', 'exam_attempts.student_id')
            ->leftJoin('exams', 'exams.id', '=', 'exam_attempts.exam_id');

        $recordsTotal = (clone $baseQuery)->count('exam_violations.id');

        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('students.full_name', 'like', '%' . $search . '%')
                    ->orWhere('exams.title', 'like', '%' . $search . '%')
                    ->orWhere('exam_violations.event_type', 'like', '%' . $search . '%')
                    ->orWhere('exam_attempts.status', 'like', '%' . $search . '%')
                    ->orWhere('exam_violations.exam_attempt_id', 'like', '%' . $search . '%');
            });
        }

        $recordsFiltered = (clone $baseQuery)->count('exam_violations.id');

        $columns = [
            0 => 'exam_violations.created_at',
            1 => 'students.full_name',
            2 => 'exams.title',
            3 => 'exam_violations.event_type',
            4 => 'exam_attempts.id',
            5 => 'exam_attempts.status',
        ];

        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $orderBy = $columns[$orderColumn] ?? 'exam_violations.created_at';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $rows = $baseQuery
            ->select([
                'exam_violations.id',
                'exam_violations.created_at',
                'exam_violations.exam_attempt_id',
                'exam_violations.event_type',
                'exam_violations.metadata',
                'students.full_name as student_name',
                'exams.title as exam_title',
                'exam_attempts.status as attempt_status',
            ])
            ->selectSub(function ($query) {
                $query->from('exam_violations as counted_violations')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('counted_violations.exam_attempt_id', 'exam_violations.exam_attempt_id');
            }, 'session_event_count')
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('exam_violations.id', 'desc')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($violation, $index) use ($start) {
            $metadata = is_array($violation->metadata)
                ? $violation->metadata
                : (json_decode($violation->metadata ?? '', true) ?: []);

            $eventLabel = match ($violation->event_type) {
                'tab_hidden' => 'Berpindah tab / halaman',
                'fullscreen_exit' => 'Keluar dari fullscreen',
                default => 'Aktivitas perlu ditinjau',
            };

            return [
                'no' => $start + $index + 1,
                'id' => $violation->id,
                'created_date' => $violation->created_at
                    ? \\Illuminate\\Support\\Carbon::parse($violation->created_at)->format('d-m-Y')
                    : '-',
                'created_time' => $violation->created_at
                    ? \\Illuminate\\Support\\Carbon::parse($violation->created_at)->format('H:i:s')
                    : '-',
                'student_name' => $violation->student_name ?: 'Peserta tidak tersedia',
                'exam_attempt_id' => $violation->exam_attempt_id,
                'exam_title' => $violation->exam_title ?: 'Ujian tidak tersedia',
                'event_label' => $eventLabel,
                'visibility_state' => $metadata['visibility_state'] ?? null,
                'session_event_count' => (int) $violation->session_event_count,
                'attempt_status' => $violation->attempt_status,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
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
