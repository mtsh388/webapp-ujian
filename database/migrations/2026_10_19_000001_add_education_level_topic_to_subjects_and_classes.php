<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('education_level', 10)->nullable()->after('code');
            $table->unsignedTinyInteger('grade_level')->nullable()->after('education_level');
            $table->index(['education_level', 'grade_level']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('education_level', 10)->nullable()->after('code');
            $table->string('topic', 150)->nullable()->after('education_level');
            $table->index(['education_level', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['education_level', 'topic']);
            $table->dropColumn(['education_level', 'topic']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['education_level', 'grade_level']);
            $table->dropColumn(['education_level', 'grade_level']);
        });
    }
};
