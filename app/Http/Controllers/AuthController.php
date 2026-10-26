<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\ParentModel;
use App\Models\RegistrationPayment;
use App\Models\RegistrationFee;
use App\Models\PromoCode;
use App\Models\Student;
use App\Models\User;
use App\Services\ExamService;
use Spatie\Permission\Models\Role;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function __construct(protected ExamService $examService)
    {
    }

    public function index(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        $classes = \App\Models\ClassRoom::query()
            ->where('is_active', true)
            ->whereIn('education_level', ['SD', 'SMP', 'SMA'])
            ->whereNotNull('grade_level')
            ->orderByRaw("CASE education_level WHEN 'SD' THEN 1 WHEN 'SMP' THEN 2 WHEN 'SMA' THEN 3 ELSE 4 END")
            ->orderBy('grade_level')
            ->get();

        $fees = RegistrationFee::active()->registration()->get()->keyBy(fn ($fee) => RegistrationFee::keyFor($fee->education_level, $fee->grade_level));
        return view('auth.register', compact('classes', 'fees'));
    }

    public function register(Request $request): RedirectResponse
    {
        if (array_key_exists('installments', $request->all())) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'payment_proof']))
                ->withErrors([
                    'registration_type' => 'Registrasi tidak menerima pembayaran cicilan.',
                ]);
        }

        $type = $request->input('registration_type', 'trial');

        $validated = $request->validate([
            'registration_type' => ['required', Rule::in(['trial', 'paid'])],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'full_name' => [Rule::requiredIf($type === 'paid'), 'nullable', 'string', 'max:255'],
            'school_name' => [Rule::requiredIf($type === 'paid'), 'nullable', 'string', 'max:255'],
            'education_level' => [Rule::requiredIf($type === 'paid'), 'nullable', Rule::in(['SD', 'SMP', 'SMA'])],
            'class_id' => [
                Rule::requiredIf($type === 'paid'),
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(fn ($query) => $query
                    ->where('education_level', $request->input('education_level'))
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'gender' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => [Rule::requiredIf($type === 'paid'), 'nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_full_name' => [Rule::requiredIf($type === 'paid'), 'nullable', 'string', 'max:255'],
            'parent_email' => [Rule::requiredIf($type === 'paid'), 'nullable', 'email', 'max:255', 'different:email', 'unique:users,email'],
            'parent_phone' => [Rule::requiredIf($type === 'paid'), 'nullable', 'string', 'max:30'],
            'parent_address' => ['nullable', 'string', 'max:255'],
        ], [
            'email.unique' => 'Email sudah terdaftar. Silakan login atau gunakan email lain.',
            'parent_email.unique' => 'Email orang tua sudah digunakan oleh akun lain.',
            'class_id.exists' => 'Kelas tidak sesuai dengan jenjang yang dipilih atau sudah tidak aktif.',
        ]);

        $user = null;

        if ($type === 'paid') {
            $classRoom = \App\Models\ClassRoom::query()
                ->whereKey($validated['class_id'])
                ->where('is_active', true)
                ->where('education_level', $validated['education_level'])
                ->whereNull('deleted_at')
                ->firstOrFail();

            $registrationFee = RegistrationFee::query()
                ->registration()
                ->active()
                ->where('education_level', $classRoom->education_level)
                ->where('grade_level', $classRoom->grade_level)
                ->first();

            if (! $registrationFee) {
                return back()
                    ->withInput($request->except(['password', 'payment_proof']))
                    ->withErrors(['class_id' => 'Biaya pendaftaran untuk jenjang dan kelas ini belum tersedia atau tidak aktif.']);
            }

            $amount = $registrationFee->amount;

            try {
                $registrationResult = DB::transaction(function () use ($validated, $classRoom, $amount): array {
                    $studentUser = User::create([
                        'name' => trim($validated['full_name']),
                        'email' => $validated['email'],
                        'password' => $validated['password'],
                        'is_active' => false,
                    ]);

                    $studentUser->forceFill([
                        'must_change_password' => false,
                        'password_changed_at' => now(),
                    ])->save();

                    $studentUser->assignRole($this->studentRole());

                    $student = Student::create([
                        'user_id' => $studentUser->id,
                        'class_id' => $classRoom->id,
                        'student_number' => 'REG-' . str_pad((string) $studentUser->id, 8, '0', STR_PAD_LEFT),
                        'full_name' => trim($validated['full_name']),
                        'school_name' => trim($validated['school_name']),
                        'education_level' => $classRoom->education_level,
                        'gender' => $validated['gender'] ?? null,
                        'birth_date' => $validated['birth_date'] ?? null,
                        'phone' => trim($validated['phone']),
                        'parent_phone' => trim($validated['parent_phone']),
                        'address' => $validated['address'] ?? null,
                        'is_active' => true,
                    ]);

                    $parentUser = User::create([
                        'name' => trim($validated['parent_full_name']),
                        'email' => $validated['parent_email'],
                        'password' => Str::random(48),
                        'is_active' => false,
                    ]);

                    $parentUser->forceFill([
                        'must_change_password' => true,
                    ])->save();

                    $parentUser->assignRole(Role::firstOrCreate([
                        'name' => 'Orang Tua',
                        'guard_name' => 'web',
                    ]));

                    $parent = ParentModel::create([
                        'user_id' => $parentUser->id,
                        'full_name' => trim($validated['parent_full_name']),
                        'phone' => trim($validated['parent_phone']),
                        'address' => $validated['parent_address'] ?? null,
                        'is_active' => true,
                    ]);

                    $student->parents()->attach($parent->id, [
                        'relationship' => 'Orang Tua/Wali',
                        'is_primary' => true,
                    ]);

                    $this->examService->assignStudentToAutoExams($student->fresh('classRoom'));

                    $payment = RegistrationPayment::create([
                        'user_id' => $studentUser->id,
                        'student_id' => $student->id,
                        'amount' => $amount,
                        'status' => 'awaiting_payment',
                        'proof_path' => null,
                        'payment_type' => 'registration',
                        'promo_code' => null,
                        'installment_status' => 'not_allowed',
                    ]);

                    return ['user' => $studentUser, 'payment' => $payment];
                });
            } catch (Throwable $exception) {
                throw $exception;
            }

            $registrationPayment = $registrationResult['payment'];

            return redirect()->to(\Illuminate\Support\Facades\URL::temporarySignedRoute(
                'register.payment.show',
                now()->addDay(),
                ['registrationPayment' => $registrationPayment->id]
            ));
        }

        $user = User::create([
            'name' => 'Siswa',
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        $user->forceFill([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        $user->assignRole($this->studentRole());

        return redirect()
            ->route('login')
            ->with('status', 'Registrasi Trial berhasil. Silakan login untuk melengkapi data siswa.');
    }

    private function studentRole(): Role
    {
        $role = Role::firstOrCreate([
            'name' => 'Siswa',
            'guard_name' => 'web',
        ]);

        $permissions = \Spatie\Permission\Models\Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['dashboard.view', 'exam.view', 'exam.take', 'result.view_own'])
            ->get();

        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }

        return $role;
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()->must_change_password) {
            return redirect()->route('password.change');
        }

        if ($request->user()->hasRole('Siswa')) {
            $student = $request->user()->student;
            $profileComplete = $student
                && filled($student->full_name)
                && filled($student->school_name)
                && filled($student->phone)
                && filled($student->education_level)
                && filled($student->class_id);

            return redirect()->route($profileComplete ? 'exams.available' : 'students.profile.edit');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function change(): View
    {
        return view('auth.change_password');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password berhasil diubah.');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendForgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password telah dikirim ke email Anda.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function resetPassword(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Password berhasil direset. Silakan login dengan password baru.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function profile(): View
    {
        return view('auth.profile', ['user' => request()->user()]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
