<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Notifications\StudentClassPromotedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PromoteStudentsForNewAcademicYear extends Command
{
    protected $signature = 'students:promote-for-new-academic-year {--date= : Simulasi tanggal YYYY-MM-DD}';

    protected $description = 'Promosikan siswa aktif ke kelas tahun ajaran berikutnya setiap 1 Juli';

    public function handle(): int
    {
        $date = $this->option('date')
            ? now()->parse($this->option('date'))->startOfDay()
            : today();

        if ($date->month !== 7 || $date->day !== 1) {
            $this->error('Promosi hanya dapat dijalankan pada tanggal 1 Juli.');

            return self::FAILURE;
        }

        $promoted = 0;
        $graduated = 0;

        Student::query()
            ->with(['classRoom', 'user'])
            ->where('is_active', true)
            ->whereNotNull('access_expires_at')
            ->whereDate('access_expires_at', '>=', $date->toDateString())
            ->whereNull('graduated_at')
            ->chunkById(100, function ($students) use ($date, &$promoted, &$graduated): void {
                foreach ($students as $student) {
                    if ($student->classHistories()
                        ->where('change_reason', 'promotion')
                        ->whereDate('ended_at', $date->copy()->subDay()->toDateString())
                        ->exists()) {
                        continue;
                    }

                    $current = $student->classRoom;

                    if (! $current) {
                        continue;
                    }

                    [$nextLevel, $nextGrade] = match ([$current->education_level, (int) $current->grade_level]) {
                        ['SD', 6] => ['SMP', 7],
                        ['SMP', 9] => ['SMA', 10],
                        ['SMA', 12] => [null, null],
                        default => [$current->education_level, (int) $current->grade_level + 1],
                    };

                    DB::transaction(function () use ($student, $current, $nextLevel, $nextGrade, $date, &$promoted, &$graduated): void {
                        if ($nextLevel === null) {
                            $student->forceFill(['graduated_at' => $date->toDateString()])->save();
                            $graduated++;

                            $notification = new StudentClassPromotedNotification(
                                'Kelulusan',
                                'Selamat! Siswa telah menyelesaikan jenjang SMA kelas 12.',
                            );

                            $student->user?->notify($notification);
                            $student->parents()->with('user')->get()->each(
                                fn ($parent) => $parent->user?->notify($notification)
                            );

                            return;
                        }

                        $nextClass = ClassRoom::query()
                            ->where('education_level', $nextLevel)
                            ->where('grade_level', $nextGrade)
                            ->where('registration_type', $current->registration_type)
                            ->where('is_active', true)
                            ->first();

                        if (! $nextClass) {
                            return;
                        }

                        $student->classHistories()->create([
                            'class_id' => $current->id,
                            'education_level' => $current->education_level,
                            'grade_level' => $current->grade_level,
                            'started_at' => $student->classHistories()->latest('started_at')->value('started_at') ?? $student->created_at?->toDateString() ?? $date->toDateString(),
                            'ended_at' => $date->copy()->subDay()->toDateString(),
                            'change_reason' => 'promotion',
                        ]);

                        $student->forceFill([
                            'class_id' => $nextClass->id,
                            'education_level' => $nextLevel,
                        ])->save();

                        $promoted++;

                        $notification = new StudentClassPromotedNotification(
                            'Promosi kelas',
                            "Siswa telah dipromosikan ke {$nextClass->name}.",
                        );

                        $student->user?->notify($notification);
                        $student->parents()->with('user')->get()->each(
                            fn ($parent) => $parent->user?->notify($notification)
                        );
                    });
                }
            });

        $this->info("Promosi selesai. Dipromosikan: {$promoted}; lulus: {$graduated}.");

        return self::SUCCESS;
    }
}
