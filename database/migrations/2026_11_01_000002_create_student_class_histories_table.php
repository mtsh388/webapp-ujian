<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('education_level', 20)->nullable();
            $table->unsignedTinyInteger('grade_level')->nullable();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->string('change_reason', 30)->default('promotion');
            $table->timestamps();

            $table->index(['student_id', 'started_at']);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->date('graduated_at')->nullable()->after('access_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('graduated_at');
        });

        Schema::dropIfExists('student_class_histories');
    }
};
