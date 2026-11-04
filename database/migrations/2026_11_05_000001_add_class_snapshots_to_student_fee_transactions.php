<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->string('class_name_snapshot')->nullable()->after('student_id');
            $table->string('education_level_snapshot', 20)->nullable()->after('class_name_snapshot');
            $table->unsignedTinyInteger('grade_level_snapshot')->nullable()->after('education_level_snapshot');
        });

        DB::table('student_fee_transactions')
            ->select('id', 'student_id', 'period_starts_at')
            ->orderBy('id')
            ->chunkById(200, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    if (! $transaction->period_starts_at) {
                        continue;
                    }

                    $history = DB::table('student_class_histories')
                        ->where('student_id', $transaction->student_id)
                        ->whereDate('started_at', '<=', $transaction->period_starts_at)
                        ->where(function ($query) use ($transaction): void {
                            $query->whereNull('ended_at')
                                ->orWhereDate('ended_at', '>=', $transaction->period_starts_at);
                        })
                        ->orderBy('ended_at')
                        ->orderBy('id')
                        ->first();

                    if ($history) {
                        $class = $history->class_id
                            ? DB::table('classes')->where('id', $history->class_id)->first()
                            : null;

                        DB::table('student_fee_transactions')->where('id', $transaction->id)->update([
                            'class_name_snapshot' => $class->name ?? null,
                            'education_level_snapshot' => $history->education_level,
                            'grade_level_snapshot' => $history->grade_level,
                        ]);

                        continue;
                    }

                    $hasHistory = DB::table('student_class_histories')
                        ->where('student_id', $transaction->student_id)
                        ->exists();

                    $latestEndedAt = DB::table('student_class_histories')
                        ->where('student_id', $transaction->student_id)
                        ->whereNotNull('ended_at')
                        ->max('ended_at');

                    if (! $hasHistory || ($latestEndedAt && $transaction->period_starts_at > $latestEndedAt)) {
                        $student = DB::table('students')
                            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
                            ->where('students.id', $transaction->student_id)
                            ->select('classes.name', 'students.education_level', 'classes.grade_level')
                            ->first();

                        if ($student) {
                            DB::table('student_fee_transactions')->where('id', $transaction->id)->update([
                                'class_name_snapshot' => $student->name,
                                'education_level_snapshot' => $student->education_level,
                                'grade_level_snapshot' => $student->grade_level,
                            ]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->dropColumn([
                'class_name_snapshot',
                'education_level_snapshot',
                'grade_level_snapshot',
            ]);
        });
    }
};
