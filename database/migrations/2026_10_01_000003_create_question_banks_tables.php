<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained();
            $table->string('code', 100)->unique();
            $table->enum('type', ['multiple_choice', 'true_false']);
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->decimal('default_score', 8, 2)->default(1.00);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'type']);
            $table->index(['subject_id', 'difficulty']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->string('option_key', 10);
            $table->text('option_text')->nullable();
            $table->boolean('is_correct')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['question_bank_id', 'option_key']);
            $table->index(['question_bank_id', 'sort_order']);
        });

        Schema::create('question_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video', 'audio', 'document']);
            $table->string('disk', 50)->default('public');
            $table->string('path', 500)->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('source', 20)->default('upload');
            $table->text('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_bank_id', 'type']);
            $table->index(['question_bank_id', 'sort_order']);
        });

        Schema::create('question_option_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_option_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video', 'audio', 'document']);
            $table->string('disk', 50)->default('public');
            $table->string('path', 500);
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('source', 20)->default('upload');
            $table->text('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_option_id', 'type']);
            $table->index(['question_option_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_option_media');
        Schema::dropIfExists('question_media');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('question_banks');
    }
};
