<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'grade_level')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedTinyInteger('grade_level')->nullable()->after('education_level');
                $table->index(
                    ['education_level', 'grade_level'],
                    'subjects_education_level_grade_level_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'grade_level')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropIndex('subjects_education_level_grade_level_index');
                $table->dropColumn('grade_level');
            });
        }
    }
};
