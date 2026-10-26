<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    public function index(): View
    {
        return view('promo-codes.index', [
            'promoCodes' => PromoCode::query()->latest()->paginate(15),
            'promoCode' => new PromoCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PromoCode::create($this->validatedData($request));

        return redirect()->route('promo-codes.index')->with('success', 'Kode promo berhasil dibuat.');
    }

    public function edit(PromoCode $promoCode): View
    {
        return view('promo-codes.index', [
            'promoCodes' => PromoCode::query()->latest()->paginate(15),
            'promoCode' => $promoCode,
        ]);
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update($this->validatedData($request, $promoCode));

        return redirect()->route('promo-codes.index')->with('success', 'Kode promo berhasil diperbarui.');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->delete();

        return redirect()->route('promo-codes.index')->with('success', 'Kode promo berhasil dihapus.');
    }

    private function validatedData(Request $request, ?PromoCode $promoCode = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('promo_codes', 'code')->ignore($promoCode?->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'integer', 'min:1', 'max:100000000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Kode promo wajib diisi.',
            'code.unique' => 'Kode promo sudah digunakan.',
            'discount_value.required' => 'Nilai diskon wajib diisi.',
            'starts_at.required' => 'Tanggal dan waktu mulai wajib diisi.',
            'ends_at.required' => 'Tanggal dan waktu berakhir wajib diisi.',
            'ends_at.after_or_equal' => 'Waktu berakhir harus sama atau setelah waktu mulai.',
        ]);

        $validated['code'] = trim($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount_value' => 'Diskon persentase maksimal 100%.',
            ]);
        }

        return $validated;
    }
}
