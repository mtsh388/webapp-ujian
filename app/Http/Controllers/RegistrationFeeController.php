<?php

namespace App\Http\Controllers;

use App\Models\RegistrationFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationFeeController extends Controller
{
    public function index(): View
    {
        $fees = RegistrationFee::query()
            ->orderByRaw("CASE fee_type WHEN 'registration' THEN 1 WHEN 'monthly' THEN 2 WHEN 'exam' THEN 3 WHEN 'books' THEN 4 WHEN 'uniform' THEN 5 WHEN 'activity' THEN 6 ELSE 7 END")
            ->orderByRaw("CASE education_level WHEN 'SD' THEN 1 WHEN 'SMP' THEN 2 WHEN 'SMA' THEN 3 ELSE 4 END")
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $typeLabels = RegistrationFee::typeLabels();

        return view('registration-fees.index', compact('fees', 'typeLabels'));
    }

    public function create(): View
    {
        $typeLabels = RegistrationFee::typeLabels();

        return view('registration-fees.create', compact('typeLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        RegistrationFee::create($this->validatedData($request));

        return redirect()->route('registration-fees.index')
            ->with('success', 'Data biaya berhasil ditambahkan.');
    }

    public function edit(RegistrationFee $registrationFee): View
    {
        $typeLabels = RegistrationFee::typeLabels();

        return view('registration-fees.edit', compact('registrationFee', 'typeLabels'));
    }

    public function update(Request $request, RegistrationFee $registrationFee): RedirectResponse
    {
        $registrationFee->update($this->validatedData($request, $registrationFee));

        return redirect()->route('registration-fees.index')
            ->with('success', 'Data biaya berhasil diperbarui.');
    }

    public function destroy(RegistrationFee $registrationFee): RedirectResponse
    {
        $registrationFee->delete();

        return redirect()->route('registration-fees.index')
            ->with('success', 'Data biaya berhasil dihapus.');
    }

    private function validatedData(Request $request, ?RegistrationFee $registrationFee = null): array
    {
        $validated = $request->validate([
            'fee_type' => ['required', Rule::in(array_keys(RegistrationFee::typeLabels()))],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_period' => ['nullable', Rule::in(['once', 'monthly', 'semester', 'yearly'])],
            'education_level' => ['nullable', Rule::in(['SD', 'SMP', 'SMA'])],
            'grade_level' => ['nullable', 'integer', Rule::in([4, 5, 6, 7, 8, 9, 10, 11, 12])],
            'amount' => ['required', 'integer', 'min:0', 'max:999999999'],
            'is_active' => ['required', 'boolean'],
        ], [
            'fee_type.in' => 'Jenis biaya tidak valid.',
            'education_level.in' => 'Jenjang pendidikan tidak valid.',
            'grade_level.in' => 'Tingkat kelas tidak valid.',
            'amount.min' => 'Biaya tidak boleh negatif.',
        ]);

        if ($validated['fee_type'] === 'registration') {
            $request->validate([
                'education_level' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
                'grade_level' => ['required', 'integer', Rule::in([4, 5, 6, 7, 8, 9, 10, 11, 12])],
            ]);

            $allowedGrades = match ($validated['education_level']) {
                'SD' => [4, 5, 6],
                'SMP' => [7, 8, 9],
                'SMA' => [10, 11, 12],
            };

            if (! in_array((int) $validated['grade_level'], $allowedGrades, true)) {
                throw ValidationException::withMessages([
                    'grade_level' => 'Tingkat kelas tidak sesuai dengan jenjang pendidikan.',
                ]);
            }

            $duplicate = RegistrationFee::query()
                ->registration()
                ->where('education_level', $validated['education_level'])
                ->where('grade_level', $validated['grade_level'])
                ->when($registrationFee, fn ($query) => $query->whereKeyNot($registrationFee->id))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'grade_level' => 'Biaya pendaftaran untuk jenjang dan kelas ini sudah ada. Silakan edit data yang tersedia.',
                ]);
            }

            $validated['name'] = 'Biaya Pendaftaran';
            $validated['billing_period'] = 'once';
        } else {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            if (filled($validated['education_level']) && filled($validated['grade_level'])) {
                $allowedGrades = match ($validated['education_level']) {
                    'SD' => [4, 5, 6],
                    'SMP' => [7, 8, 9],
                    'SMA' => [10, 11, 12],
                };

                if (! in_array((int) $validated['grade_level'], $allowedGrades, true)) {
                    throw ValidationException::withMessages([
                        'grade_level' => 'Tingkat kelas tidak sesuai dengan jenjang pendidikan.',
                    ]);
                }
            } elseif (filled($validated['grade_level']) && blank($validated['education_level'])) {
                throw ValidationException::withMessages([
                    'education_level' => 'Pilih jenjang pendidikan jika kelas ditentukan.',
                ]);
            }
        }

        $validated['education_level'] = $validated['education_level'] ?: null;
        $validated['grade_level'] = filled($validated['grade_level']) ? (int) $validated['grade_level'] : null;
        $validated['amount'] = (int) $validated['amount'];
        $validated['is_active'] = (bool) $validated['is_active'];

        return $validated;
    }
}
