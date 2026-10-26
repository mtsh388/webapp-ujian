<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    public function index(): View
    {
        return view('promo-codes.index', [
            'promoCode' => new PromoCode(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PromoCode::query();
        $recordsTotal = (clone $query)->count();

        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('discount_type', 'like', "%{$search}%")
                    ->orWhere('discount_value', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumns = [
            1 => 'code',
            2 => 'discount_type',
            3 => 'starts_at',
            4 => 'ends_at',
            5 => 'is_active',
        ];

        $orderIndex = (int) $request->input('order.0.column', 3);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        if (isset($orderColumns[$orderIndex])) {
            $query->orderBy($orderColumns[$orderIndex], $orderDirection);
        } else {
            $query->latest('id');
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 10 : min($length, 100);

        $data = $query->skip($start)->take($length)->get()->map(function (PromoCode $promoCode) {
            return [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'name' => $promoCode->name,
                'discount_type' => $promoCode->discount_type,
                'discount_value' => $promoCode->discount_value,
                'starts_at' => $promoCode->starts_at?->format('d-m-Y H:i'),
                'ends_at' => $promoCode->ends_at?->format('d-m-Y H:i'),
                'is_active' => (bool) $promoCode->is_active,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
            'promoCode' => $promoCode,
        ]);
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update($this->validatedData($request, $promoCode));

        return redirect()->route('promo-codes.index')->with('success', 'Kode promo berhasil diperbarui.');
    }

    public function updateStatus(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $promoCode->update([
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'status' => true,
            'message' => $promoCode->is_active
                ? 'Kode promo berhasil diaktifkan.'
                : 'Kode promo berhasil dinonaktifkan.',
            'is_active' => (bool) $promoCode->is_active,
        ]);
    }

    public function destroy(PromoCode $promoCode): JsonResponse|RedirectResponse
    {
        $promoCode->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Kode promo berhasil dihapus.',
            ]);
        }

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
