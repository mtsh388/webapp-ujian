<?php

namespace App\Http\Controllers;

use App\Models\ExamAttemptApproval;
use App\Notifications\StudentClassPromotedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamAttemptApprovalController extends Controller
{
    public function index(): View
    {
        return view('exam-attempt-approvals.index', [
            'approvals' => ExamAttemptApproval::query()
                ->with(['exam.subject', 'student.user', 'student.classRoom', 'reviewer'])
                ->latest('requested_at')
                ->paginate(20),
        ]);
    }

    public function approve(ExamAttemptApproval $approval): RedirectResponse
    {
        if ($approval->status !== 'pending') {
            return redirect()->route('exam-attempt-approvals.index')->with('error', 'Permintaan ini sudah diproses.');
        }

        $attemptCount = \App\Models\ExamAttempt::query()
            ->where('exam_id', $approval->exam_id)
            ->where('student_id', $approval->student_id)
            ->count();

        if ($attemptCount !== 1) {
            return redirect()->route('exam-attempt-approvals.index')->with('error', 'Persetujuan hanya dapat diberikan untuk percobaan kedua.');
        }

        $approval->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        try {
            $approval->loadMissing('student.user');
            $approval->student?->user?->notify(new StudentClassPromotedNotification(
                'Permintaan percobaan kedua disetujui',
                'Permintaan percobaan kedua untuk ujian ' . ($approval->exam?->title ?? '') . ' telah disetujui.'
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('exam-attempt-approvals.index')->with('success', 'Percobaan kedua berhasil disetujui.');
    }

    public function reject(Request $request, ExamAttemptApproval $approval): RedirectResponse
    {
        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        if ($approval->status !== 'pending') {
            return redirect()->route('exam-attempt-approvals.index')->with('error', 'Permintaan ini sudah diproses.');
        }

        $approval->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'],
        ]);

        try {
            $approval->loadMissing('student.user');
            $approval->student?->user?->notify(new StudentClassPromotedNotification(
                'Permintaan percobaan kedua ditolak',
                'Permintaan percobaan kedua untuk ujian ' . ($approval->exam?->title ?? '') . ' ditolak. Alasan: ' . $validated['review_note']
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('exam-attempt-approvals.index')->with('success', 'Permintaan percobaan kedua ditolak.');
    }
}
