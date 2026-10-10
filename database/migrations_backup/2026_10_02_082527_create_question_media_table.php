<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('question_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')
                ->constrained('question_banks')
                ->cascadeOnDelete();

            $table->enum('type', [
                'image',
                'video',
                'audio',
                'document',
            ]);

            $table->string('disk', 50)->default('public');
            $table->string('path', 500)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('source', 20)->default('upload');
            $table->string('external_url', 500)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index([
                'question_bank_id',
                'type',
            ]);

            $table->index([
                'question_bank_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_media');
    }
};
