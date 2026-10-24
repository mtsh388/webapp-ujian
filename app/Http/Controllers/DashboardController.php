<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasRole('Admin')) {
            return view('dashboard.admin', [
                'totalStudents' => Student::count(),
                'totalTentors' => User::role('Tentor')->count(),
                'totalExams' => Exam::count(),
                'totalQuestions' => QuestionBank::count(),
                'recentExams' => Exam::latest()->limit(5)->get(),
            ]);
        }

        if ($user->hasRole('Tentor')) {
            return view('dashboard.tentor', [
                'totalQuestions' => QuestionBank::count(),
                'totalExams' => Exam::count(),
                'totalStudents' => Student::count(),
                'recentExams' => Exam::latest()->limit(5)->get(),
            ]);
        }

        if ($user->hasRole('Siswa')) {
            $student = $user->student;
            $outstandingInvoices = $student
                ? BillingInvoice::query()->where('student_id', $student->id)->where('payment_status', '!=', 'paid')->orderBy('due_date')->get()
                : collect();

            $availableExams = Exam::query()
                ->where('status', 'published')
                ->count();

            $totalAttempts = $student
                ? ExamAttempt::where('student_id', $student->id)->count()
                : 0;

            $completedAttempts = $student
                ? ExamAttempt::where('student_id', $student->id)
                    ->where('status', 'submitted')
                    ->count()
                : 0;

            $recentAttempts = $student
                ? ExamAttempt::with('exam')
                    ->where('student_id', $student->id)
                    ->latest()
                    ->limit(5)
                    ->get()
                : collect();

            return view('dashboard.siswa', [
                'student' => $student,
                'availableExams' => $availableExams,
                'totalAttempts' => $totalAttempts,
                'completedAttempts' => $completedAttempts,
                'recentAttempts' => $recentAttempts,
                'outstandingInvoices' => $outstandingInvoices,
            ]);
        }

        if ($user->hasRole('Orang Tua')) {
            $parent = $user->parentProfile;

            $children = $parent
                ? $parent->students()->with('classRoom')->get()
                : collect();

            $studentIds = $children->pluck('id');
            $outstandingInvoices = BillingInvoice::query()
                ->with('student')
                ->whereIn('student_id', $studentIds)
                ->where('payment_status', '!=', 'paid')
                ->orderBy('due_date')
                ->get();

            $totalAttempts = ExamAttempt::whereIn('student_id', $studentIds)->count();

            $completedAttempts = ExamAttempt::whereIn('student_id', $studentIds)
                ->where('status', 'submitted')
                ->count();

            $recentAttempts = ExamAttempt::with(['exam', 'student'])
                ->whereIn('student_id', $studentIds)
                ->latest()
                ->limit(5)
                ->get();

            return view('dashboard.orang-tua', [
                'parent' => $parent,
                'children' => $children,
                'totalAttempts' => $totalAttempts,
                'completedAttempts' => $completedAttempts,
                'recentAttempts' => $recentAttempts,
                'outstandingInvoices' => $outstandingInvoices,
            ]);
        }

        abort(403);
    }
}
