<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 100)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->decimal('passing_score', 8, 2)->default(75.00);
            $table->unsignedInteger('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(1);
            $table->boolean('shuffle_options')->default(1);
            $table->boolean('show_result_immediately')->default(1);
            $table->boolean('show_correct_answers')->default(0);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'status']);
            $table->index(['start_at', 'end_at']);
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_bank_id')->nullable()->constrained('question_banks')->nullOnDelete();
            $table->enum('type', ['multiple_choice', 'true_false']);
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->decimal('score', 8, 2)->default(1.00);
            $table->unsignedInteger('sort_order');
            $table->timestamps();

            $table->unique(['exam_id', 'sort_order']);
            $table->index(['exam_id', 'question_bank_id']);
        });

        Schema::create('exam_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->string('option_key', 10);
            $table->text('option_text');
            $table->boolean('is_correct')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['exam_question_id', 'option_key']);
            $table->index(['exam_question_id', 'sort_order']);
        });

        Schema::create('exam_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['student_id', 'is_active']);
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('wrong_answers')->default(0);
            $table->unsignedInteger('unanswered')->default(0);
            $table->decimal('total_score', 8, 2)->default(0.00);
            $table->decimal('score', 8, 2)->default(0.00);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'expired', 'cancelled'])->default('not_started');
            $table->boolean('passed')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'attempt_number']);
            $table->index(['student_id', 'status']);
            $table->index(['exam_id', 'status']);
        });

        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('exam_question_options')->nullOnDelete();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 8, 2)->default(0.00);
            $table->dateTime('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'exam_question_id']);
            $table->index(['exam_attempt_id', 'is_correct']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_participants');
        Schema::dropIfExists('exam_question_options');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
    }
};
