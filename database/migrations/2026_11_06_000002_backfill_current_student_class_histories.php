<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->whereNotNull('class_id')
            ->whereNull('graduated_at')
            ->orderBy('id')
            ->chunkById(100, function ($students): void {
                foreach ($students as $student) {
                    $hasOpenHistory = DB::table('student_class_histories')
                        ->where('student_id', $student->id)
                        ->whereNull('ended_at')
                        ->exists();

                    if ($hasOpenHistory) {
                        continue;
                    }

                    $class = DB::table('classes')
                        ->where('id', $student->class_id)
                        ->first();

                    if (! $class) {
                        continue;
                    }

                    $latestHistory = DB::table('student_class_histories')
                        ->where('student_id', $student->id)
                        ->orderByDesc('started_at')
                        ->orderByDesc('id')
                        ->first();

                    $startedAt = $latestHistory?->ended_at
                        ? Carbon::parse($latestHistory->ended_at)->addDay()->startOfDay()
                        : Carbon::parse($student->created_at ?? now())->startOfDay();

                    if ($startedAt->isFuture()) {
                        $startedAt = Carbon::parse($student->created_at ?? now())->startOfDay();
                    }

                    if ($startedAt->isFuture()) {
                        $startedAt = Carbon::today();
                    }

                    DB::table('student_class_histories')->insert([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'education_level' => $class->education_level,
                        'grade_level' => $class->grade_level,
                        'started_at' => $startedAt->toDateString(),
                        'ended_at' => null,
                        'change_reason' => 'backfill',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
    }
};
