<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->string('code', 100)->unique();
            $table->enum('type', ['multiple_choice', 'true_false']);
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->decimal('default_score', 8, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'type']);
            $table->index(['subject_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
