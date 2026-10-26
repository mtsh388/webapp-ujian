<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('schedule_mode', 40)->default('scheduled')->after('has_duration');
        });

        DB::table('exams')->where('has_duration', false)->update(['schedule_mode' => 'no_duration']);
        DB::table('exams')
            ->where('has_duration', true)
            ->where(function ($query) {
                $query->whereNull('start_at')->orWhereNull('end_at');
            })
            ->update(['schedule_mode' => 'duration_without_schedule']);
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('schedule_mode');
        });
    }
};
