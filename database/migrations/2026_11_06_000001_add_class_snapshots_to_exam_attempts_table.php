<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('class_name_snapshot')->nullable()->after('student_id');
            $table->string('education_level_snapshot')->nullable()->after('class_name_snapshot');
            $table->unsignedTinyInteger('grade_level_snapshot')->nullable()->after('education_level_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'class_name_snapshot',
                'education_level_snapshot',
                'grade_level_snapshot',
            ]);
        });
    }
};
