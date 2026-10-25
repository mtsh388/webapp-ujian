<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_fees', function (Blueprint $table): void {
            $table->id();
            $table->string('education_level', 10);
            $table->unsignedTinyInteger('grade_level');
            $table->unsignedBigInteger('amount');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['education_level', 'grade_level']);
            $table->index(['is_active', 'education_level', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_fees');
    }
};
