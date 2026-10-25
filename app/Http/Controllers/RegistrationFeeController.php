<?php

namespace App\Http\Controllers;

use App\Models\RegistrationFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationFeeController extends Controller
{
    public function index(): View
    {
        $typeLabels = RegistrationFee::typeLabels();

        return view('registration-fees.index', compact('typeLabels'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = RegistrationFee::query();
        $recordsTotal = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('fee_type_label', 'like', "%{$search}%")
                    ->orWhere('fee_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('education_level', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();
        $columns = [
            0 => 'fee_type_label',
            1 => 'education_level',
            2 => 'grade_level',
            3 => 'billing_period',
            4 => 'amount',
            5 => 'discount_enabled',
            6 => 'max_installments',
            7 => 'is_active',
        ];
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderIndex] ?? 'fee_type_label';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($orderColumn, $orderDirection)
            ->orderBy('id');

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? 100 : min(max($length, 1), 100);

        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        return view('registration-fees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        RegistrationFee::create($this->validatedData($request));

        return redirect()->route('registration-fees.index')
            ->with('success', 'Data biaya berhasil ditambahkan.');
    }

    public function edit(RegistrationFee $registrationFee): View
    {
        return view('registration-fees.edit', compact('registrationFee'));
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
            'fee_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_period' => ['nullable', Rule::in(['once', 'monthly', 'semester', 'yearly'])],
            'education_level' => ['nullable', Rule::in(['SD', 'SMP', 'SMA'])],
            'grade_level' => ['nullable', 'integer', Rule::in([4, 5, 6, 7, 8, 9, 10, 11, 12])],
            'amount' => ['required', 'integer', 'min:0', 'max:999999999'],
            'is_active' => ['required', 'boolean'],
            'discount_enabled' => ['nullable', 'boolean'],
            'max_installments' => ['nullable', 'integer', 'min:1', 'max:5'],
        ], [
            'fee_type.required' => 'Nama biaya wajib diisi.',
            'fee_type.max' => 'Nama biaya maksimal 100 karakter.',
            'education_level.in' => 'Jenjang pendidikan tidak valid.',
            'grade_level.in' => 'Tingkat kelas tidak valid.',
            'amount.min' => 'Biaya tidak boleh negatif.',
            'max_installments.max' => 'Cicilan maksimal 5 kali.',
        ]);

        $feeTypeLabel = trim($validated['fee_type']);
        $feeTypeSlug = Str::slug($feeTypeLabel);

        if ($feeTypeSlug === '') {
            throw ValidationException::withMessages([
                'fee_type' => 'Nama biaya harus berisi huruf atau angka.',
            ]);
        }

        $knownTypes = [];
        $knownLabels = [];
        foreach (RegistrationFee::typeLabels() as $key => $label) {
            $knownTypes[Str::slug($key)] = $key;
            $knownTypes[Str::slug($label)] = $key;
            $knownLabels[$key] = $label;
        }
        $knownTypes['biaya-pendaftaran'] = 'registration';
        $knownTypes['pendaftaran'] = 'registration';

        $feeType = $knownTypes[$feeTypeSlug] ?? $feeTypeSlug;

        if (str_starts_with($feeTypeSlug, 'pendaftaran-') || str_starts_with($feeTypeSlug, 'biaya-pendaftaran-')) {
            $feeType = 'registration';
        }

        $isRegistration = $feeType === 'registration';

        if (isset($knownLabels[$feeType]) && in_array($feeTypeSlug, [Str::slug($feeType), Str::slug($knownLabels[$feeType])], true)) {
            $feeTypeLabel = $knownLabels[$feeType];
        }

        if ($isRegistration) {
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
                ->when($registrationFee, fn ($query) => $query->where('id', '!=', $registrationFee->id))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'grade_level' => 'Biaya pendaftaran untuk jenjang dan kelas ini sudah ada. Silakan edit data yang tersedia.',
                ]);
            }

            $validated['billing_period'] = 'once';
            $validated['discount_enabled'] = false;
            $validated['promo_code'] = null;
            $validated['promo_starts_at'] = null;
            $validated['promo_ends_at'] = null;
            $validated['discount_type'] = null;
            $validated['discount_value'] = null;
            $validated['max_installments'] = 1;
        } else {
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

            $discountEnabled = (bool) ($validated['discount_enabled'] ?? false);
            $validated['discount_enabled'] = $discountEnabled;
            $validated['max_installments'] = (int) ($validated['max_installments'] ?? 1);

            if ($discountEnabled) {
                unset(
                    $validated['promo_code'],
                    $validated['promo_starts_at'],
                    $validated['promo_ends_at'],
                    $validated['discount_type'],
                    $validated['discount_value']
                );
            } else {
                $validated['promo_code'] = null;
                $validated['promo_starts_at'] = null;
                $validated['promo_ends_at'] = null;
                $validated['discount_type'] = null;
                $validated['discount_value'] = null;
            }
        }

        $validated['fee_type'] = $feeType;
        $validated['fee_type_label'] = $feeTypeLabel;
        $validated['name'] = $feeTypeLabel;
        $validated['education_level'] = $validated['education_level'] ?: null;
        $validated['grade_level'] = filled($validated['grade_level']) ? (int) $validated['grade_level'] : null;
        $validated['amount'] = (int) $validated['amount'];
        $validated['is_active'] = (bool) $validated['is_active'];

        return $validated;
    }
}
