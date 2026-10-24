<?php

namespace App\Console\Commands;

use App\Models\BillingGenerationSchedule;
use App\Models\BillingInvoice;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateScheduledBillingInvoices extends Command
{
    protected $signature = 'billing:generate-scheduled';
    protected $description = 'Generate invoices from active billing schedules whose run time has arrived';

    public function handle(): int
    {
        $schedules = BillingGenerationSchedule::query()
            ->where('is_active', true)
            ->whereNull('last_run_at')
            ->where('scheduled_at', '<=', now())
            ->with(['feeRate.feeType', 'feeRate.academicYear'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($schedules as $schedule) {
            try {
                $created = DB::transaction(function () use ($schedule): int {
                    $locked = BillingGenerationSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
                    if (!$locked->is_active || $locked->last_run_at || $locked->scheduled_at->isFuture()) {
                        return 0;
                    }

                    $rate = $locked->feeRate()->with(['feeType', 'academicYear'])->firstOrFail();
                    if (!$rate->is_active || !$rate->feeType->is_active || !$rate->academicYear->is_active) {
                        $locked->forceFill([
                            'last_run_at' => now(),
                            'generated_count' => 0,
                            'last_result' => 'Dilewati: tarif, jenis biaya, atau tahun ajaran tidak aktif.',
                        ])->save();
                        return 0;
                    }

                    $students = Student::query()
                        ->where('is_active', true)
                        ->when($rate->class_id !== null, fn ($query) => $query->where('class_id', $rate->class_id))
                        ->orderBy('id')
                        ->get(['id', 'class_id']);

                    $createdCount = 0;
                    foreach ($students as $student) {
                        $exists = BillingInvoice::query()
                            ->where('student_id', $student->id)
                            ->where('fee_rate_id', $rate->id)
                            ->where('period_label', $locked->period_label)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        BillingInvoice::query()->create([
                            'student_id' => $student->id,
                            'fee_rate_id' => $rate->id,
                            'title' => $rate->feeType->name,
                            'period_type' => $rate->period_type,
                            'period_label' => $locked->period_label,
                            'amount' => $rate->amount,
                            'due_date' => $locked->due_date->toDateString(),
                            'notes' => 'Dibuat otomatis oleh jadwal tagihan.',
                            'payment_status' => 'unpaid',
                            'created_by' => $locked->created_by,
                        ]);
                        $createdCount++;
                    }

                    $locked->forceFill([
                        'last_run_at' => now(),
                        'generated_count' => $createdCount,
                        'last_result' => sprintf('Selesai. %d tagihan baru dibuat; tagihan yang sudah ada dilewati.', $createdCount),
                    ])->save();

                    return $createdCount;
                });

                $this->info("Jadwal #{$schedule->id}: {$created} tagihan dibuat.");
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Jadwal #{$schedule->id} gagal: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
