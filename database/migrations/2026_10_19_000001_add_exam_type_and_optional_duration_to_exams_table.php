<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_type', 20)->default('ujian');
            $table->boolean('has_duration')->default(true);
            $table->unsignedInteger('duration_minutes')->nullable()->change();
            $table->dateTime('start_at')->nullable()->change();
            $table->dateTime('end_at')->nullable()->change();
            $table->unsignedInteger('max_attempts')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['exam_type', 'has_duration']);
        });
    }
};
