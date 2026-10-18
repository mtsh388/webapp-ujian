<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('school_name')->nullable()->after('full_name');
            $table->string('education_level', 10)->nullable()->after('school_name');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->string('education_level', 10)->nullable()->after('code');
            $table->unsignedTinyInteger('grade_level')->nullable()->after('education_level');
            $table->index(['education_level', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['education_level', 'grade_level']);
            $table->dropColumn(['education_level', 'grade_level']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'education_level']);
        });
    }
};
