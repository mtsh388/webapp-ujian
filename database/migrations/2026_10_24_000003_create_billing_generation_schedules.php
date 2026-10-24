<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_generation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_rate_id')->constrained('billing_fee_rates')->cascadeOnDelete();
            $table->string('period_label', 80);
            $table->dateTime('scheduled_at');
            $table->date('due_date');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('generated_count')->default(0);
            $table->text('last_result')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fee_rate_id', 'scheduled_at', 'period_label'], 'billing_schedule_unique');
            $table->index(['is_active', 'scheduled_at']);
        });

        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->unique(['student_id', 'fee_rate_id', 'period_label'], 'billing_invoice_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropUnique('billing_invoice_period_unique');
        });
        Schema::dropIfExists('billing_generation_schedules');
    }
};
