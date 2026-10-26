<?php

namespace App\\Http\\Controllers;

use App\\Models\\RegistrationPayment;
use Illuminate\\View\\View;

class MyRegistrationPaymentController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $query = RegistrationPayment::query()
            ->with(['user', 'student.classRoom'])
            ->latest();

        if ($user->hasRole('Siswa')) {
            $student = $user->student;

            $query->where(function ($builder) use ($student, $user) {
                $builder->where('user_id', $user->id);

                if ($student) {
                    $builder->orWhere('student_id', $student->id);
                }
            });
        } elseif ($user->hasRole('Orang Tua')) {
            $parent = $user->parentProfile;
            $studentIds = $parent
                ? $parent->students()->pluck('students.id')
                : collect();

            $query->whereIn('student_id', $studentIds);
        } else {
            abort(403);
        }

        return view('my-registration-payments.index', [
            'payments' => $query->paginate(15),
            'isParent' => $user->hasRole('Orang Tua'),
        ]);
    }
}
