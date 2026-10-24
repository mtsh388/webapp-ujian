<?php

namespace App\\Http\\Controllers;

use App\\Models\\BillingFeeRate;
use App\\Models\\BillingGenerationSchedule;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

class BillingScheduleController extends Controller
{
    public function index(): View
    {
        return view('billing.schedules', [
            'feeRates' => BillingFeeRate::query()
                ->with(['feeType', 'academicYear', 'classRoom'])
                ->where('is_active', true)
                ->whereHas('feeType', fn ($query) => $query->where('is_active', true))
                ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
                ->orderBy('period_label')
                ->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $baseQuery = BillingGenerationSchedule::query()
            ->with(['feeRate.feeType', 'feeRate.academicYear', 'feeRate.classRoom']);

        $recordsTotal = (clone $baseQuery)->count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('period_label', 'like', '%' . $search . '%')
                    ->orWhere('last_result', 'like', '%' . $search . '%')
                    ->orWhereHas('feeRate.feeType', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('feeRate.academicYear', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('feeRate.classRoom', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();
        $orderColumns = [
            0 => 'id',
            1 => 'scheduled_at',
            2 => 'due_date',
            3 => 'generated_count',
            4 => 'is_active',
        ];
        $orderIndex = (int) $request->input('order.0.column', 1);
        $orderColumn = $orderColumns[$orderIndex] ?? 'scheduled_at';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? 100 : max(1, min($length, 100));
        $start = max(0, (int) $request->input('start', 0));

        $schedules = $baseQuery
            ->orderBy($orderColumn, $orderDirection)
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $rows = $schedules->map(function (BillingGenerationSchedule $schedule) {
            $rate = $schedule->feeRate;
            return [
                'id' => $schedule->id,
                'fee_type' => $rate?->feeType?->name ?? 'Tarif dihapus',
                'period_label' => $schedule->period_label,
                'academic_year' => $rate?->academicYear?->name ?? '-',
                'class_name' => $rate?->classRoom?->name ?? 'Semua kelas',
                'scheduled_at' => $schedule->scheduled_at?->format('d/m/Y H:i') ?? '-',
                'due_date' => $schedule->due_date?->format('d/m/Y') ?? '-',
                'generated_count' => $schedule->generated_count,
                'last_run_at' => $schedule->last_run_at?->format('d/m/Y H:i'),
                'last_result' => $schedule->last_result,
                'is_active' => $schedule->is_active,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fee_rate_id' => ['required', 'integer', 'exists:billing_fee_rates,id'],
            'period_label' => ['required', 'string', 'max:80'],
            'scheduled_at' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rate = BillingFeeRate::query()->with(['feeType', 'academicYear'])->findOrFail($data['fee_rate_id']);
        abort_unless($rate->is_active && $rate->feeType->is_active && $rate->academicYear->is_active, 422, 'Tarif, jenis biaya, dan tahun ajaran harus aktif.');
        abort_if(now()->parse($data['scheduled_at'])->isPast(), 422, 'Waktu jadwal harus sekarang atau di masa mendatang.');
        abort_if($data['due_date'] < now()->parse($data['scheduled_at'])->toDateString(), 422, 'Jatuh tempo tidak boleh sebelum waktu pembuatan tagihan.');

        BillingGenerationSchedule::query()->create([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Jadwal generate tagihan berhasil disimpan. Pastikan Laravel Scheduler aktif di server.');
    }

    public function toggle(BillingGenerationSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->last_run_at !== null, 422, 'Jadwal yang sudah dijalankan tidak dapat diaktifkan kembali. Buat jadwal baru untuk periode berikutnya.');
        $schedule->update(['is_active' => !$schedule->is_active]);

        return back()->with('success', 'Status jadwal diperbarui.');
    }

    public function destroy(BillingGenerationSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->last_run_at !== null, 422, 'Jadwal yang sudah dijalankan tidak dapat dihapus dari riwayat.');
        $schedule->delete();

        return back()->with('success', 'Jadwal dihapus.');
    }
}
