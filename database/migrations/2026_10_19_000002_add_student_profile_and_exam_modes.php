<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('topic')->constrained('classes')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('school_name')->nullable()->after('full_name');
            $table->string('education_level', 10)->nullable()->after('school_name');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_type', 20)->default('exam')->after('subject_id');
            $table->boolean('is_timed')->default(true)->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['exam_type', 'is_timed']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'education_level']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_id');
        });
    }
};