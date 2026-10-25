<?php

namespace App\Http\Controllers;

use App\Models\RegistrationFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationFeeController extends Controller
{
    public function index(): View
    {
        $fees = RegistrationFee::query()
            ->orderByRaw("CASE education_level WHEN 'SD' THEN 1 WHEN 'SMP' THEN 2 WHEN 'SMA' THEN 3 ELSE 4 END")
            ->orderBy('grade_level')
            ->get();

        return view('registration-fees.index', compact('fees'));
    }

    public function create(): View
    {
        return view('registration-fees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        RegistrationFee::create($this->validatedData($request));

        return redirect()->route('registration-fees.index')
            ->with('success', 'Biaya pendaftaran berhasil ditambahkan.');
    }

    public function edit(RegistrationFee $registrationFee): View
    {
        return view('registration-fees.edit', compact('registrationFee'));
    }

    public function update(Request $request, RegistrationFee $registrationFee): RedirectResponse
    {
        $registrationFee->update($this->validatedData($request, $registrationFee));

        return redirect()->route('registration-fees.index')
            ->with('success', 'Biaya pendaftaran berhasil diperbarui.');
    }

    public function destroy(RegistrationFee $registrationFee): RedirectResponse
    {
        $registrationFee->delete();

        return redirect()->route('registration-fees.index')
            ->with('success', 'Biaya pendaftaran berhasil dihapus.');
    }

    private function validatedData(Request $request, ?RegistrationFee $registrationFee = null): array
    {
        $validated = $request->validate([
            'education_level' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
            'grade_level' => ['required', 'integer', Rule::in([4, 5, 6, 7, 8, 9, 10, 11, 12])],
            'amount' => ['required', 'integer', 'min:0', 'max:999999999'],
            'is_active' => ['required', 'boolean'],
        ], [
            'education_level.in' => 'Jenjang pendidikan tidak valid.',
            'grade_level.in' => 'Tingkat kelas tidak valid.',
            'amount.min' => 'Biaya tidak boleh negatif.',
        ]);

        $allowedGrades = match ($validated['education_level']) {
            'SD' => [4, 5, 6],
            'SMP' => [7, 8, 9],
            'SMA' => [10, 11, 12],
        };

        if (! in_array((int) $validated['grade_level'], $allowedGrades, true)) {
            return validator([], [])->validate();
        }

        $uniqueRule = Rule::unique('registration_fees', 'grade_level')
            ->where('education_level', $validated['education_level']);

        if ($registrationFee) {
            $uniqueRule->ignore($registrationFee->id);
        }

        validator($validated, [
            'grade_level' => [$uniqueRule],
        ], [
            'grade_level.unique' => 'Biaya untuk jenjang dan kelas ini sudah ada. Silakan edit data yang tersedia.',
        ])->validate();

        $validated['grade_level'] = (int) $validated['grade_level'];
        $validated['amount'] = (int) $validated['amount'];
        $validated['is_active'] = (bool) $validated['is_active'];

        return $validated;
    }
}
