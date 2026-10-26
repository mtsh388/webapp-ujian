<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteStudentProfileRequest;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {
    }

    public function edit(): View
    {
        $user = request()->user();

        abort_unless($user->hasRole('Siswa'), 403);

        $student = $user->student;

        $classes = ClassRoom::query()
            ->where('is_active', true)
            ->whereIn('education_level', ['SD', 'SMP', 'SMA'])
            ->orderByRaw("CASE education_level WHEN 'SD' THEN 1 WHEN 'SMP' THEN 2 WHEN 'SMA' THEN 3 ELSE 4 END")
            ->orderBy('grade_level')
            ->get();

        return view('students.complete-profile', compact('student', 'classes'));
    }

    public function update(CompleteStudentProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $student = Student::firstOrNew(['user_id' => $user->id]);

        if (!$student->exists) {
            $student->student_number = 'REG-' . str_pad((string) $user->id, 8, '0', STR_PAD_LEFT);
            $student->registration_type = 'trial';
            $student->is_active = true;
        }

        $student->fill([
            'full_name' => $data['full_name'],
            'school_name' => $data['school_name'],
            'phone' => $data['phone'],
            'parent_phone' => $data['parent_phone'] ?? null,
            'education_level' => $data['education_level'],
            'class_id' => $data['class_id'],
        ]);

        $student->save();

        $this->examService->assignStudentToAutoExams($student->fresh('classRoom'));

        $user->forceFill(['name' => $data['full_name']])->save();

        return redirect()
            ->route('students.profile.edit')
            ->with('success', 'Profil siswa berhasil disimpan. Anda sekarang dapat mulai ujian.');
    }
}
