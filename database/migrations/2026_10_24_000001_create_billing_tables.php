<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('period_type', ['semester', 'year']);
            $table->string('period_label', 80);
            $table->decimal('amount', 14, 2);
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'payment_status']);
            $table->index(['due_date', 'payment_status']);
        });

        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->enum('method', ['transfer', 'cash']);
            $table->date('paid_at');
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_invoices');
    }
};
