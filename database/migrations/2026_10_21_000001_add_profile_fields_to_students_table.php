<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'school_name')) {
                $table->string('school_name')->nullable()->after('full_name');
            }

            if (!Schema::hasColumn('students', 'education_level')) {
                $table->string('education_level', 10)->nullable()->after('school_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'education_level')) {
                $table->dropColumn('education_level');
            }

            if (Schema::hasColumn('students', 'school_name')) {
                $table->dropColumn('school_name');
            }
        });
    }
};
