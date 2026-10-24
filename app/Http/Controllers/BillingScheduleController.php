<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\BillingFeeRate;
use App\Models\BillingGenerationSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingScheduleController extends Controller
{
    public function index(): View
    {
        return view('billing.schedules', [
            'schedules' => BillingGenerationSchedule::query()
                ->with(['feeRate.feeType', 'feeRate.academicYear', 'feeRate.classRoom'])
                ->orderByDesc('scheduled_at')
                ->paginate(20),
            'feeRates' => BillingFeeRate::query()
                ->with(['feeType', 'academicYear', 'classRoom'])
                ->where('is_active', true)
                ->whereHas('feeType', fn ($query) => $query->where('is_active', true))
                ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
                ->orderBy('period_label')
                ->get(),
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
