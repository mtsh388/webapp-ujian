<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('Siswa')) {
            return $next($request);
        }

        $student = $user->student;

        $complete = $student
            && filled($student->full_name)
            && filled($student->school_name)
            && filled($student->phone)
            && filled($student->education_level)
            && filled($student->class_id);

        if (!$complete && !$request->routeIs('students.profile.edit', 'students.profile.update')) {
            return redirect()
                ->route('students.profile.edit')
                ->with('warning', 'Lengkapi profil siswa sebelum mulai mengerjakan ujian.');
        }

        return $next($request);
    }
}