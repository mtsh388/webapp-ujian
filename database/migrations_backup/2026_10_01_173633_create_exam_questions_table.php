<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('question_bank_id')->nullable()->constrained('question_banks')->nullOnDelete();
            $table->enum('type', ['multiple_choice', 'true_false']);
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->decimal('score', 8, 2)->default(1);
            $table->unsignedInteger('sort_order');
            $table->timestamps();

            $table->unique(['exam_id', 'sort_order']);
            $table->index(['exam_id', 'question_bank_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
