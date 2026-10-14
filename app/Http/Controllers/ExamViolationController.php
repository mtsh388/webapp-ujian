<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamViolationController extends Controller
{
    public function index(): View
    {
        $violations = ExamViolation::query()
            ->with(['attempt.exam', 'attempt.student'])
            ->withCount('attempt.violations')
            ->latest()
            ->paginate(25);

        return view('proctoring.index', [
            'violations' => $violations,
            'totalToday' => ExamViolation::whereDate('created_at', today())->count(),
            'attemptsFlagged' => ExamViolation::whereDate('created_at', today())
                ->distinct('exam_attempt_id')
                ->count('exam_attempt_id'),
            'activeAttemptsFlagged' => ExamViolation::whereHas('attempt', function ($query) {
                $query->where('status', 'in_progress');
            })->distinct('exam_attempt_id')->count('exam_attempt_id'),
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
