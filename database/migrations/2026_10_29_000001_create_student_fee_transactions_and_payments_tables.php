<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_payments', function (Blueprint $table): void {
            $table->unsignedTinyInteger('duration_years')->default(1)->after('installment_status');
            $table->unsignedTinyInteger('installment_count')->default(1)->after('duration_years');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->timestamp('access_expires_at')->nullable()->after('is_active');
        });

        Schema::create('student_fee_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_payment_id')->unique()->constrained('registration_payments')->restrictOnDelete();
            $table->unsignedTinyInteger('duration_years');
            $table->unsignedTinyInteger('installment_count');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->json('details');
            $table->date('period_starts_at');
            $table->date('period_ends_at');
            $table->string('status', 30)->default('awaiting_payment');
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['period_starts_at', 'period_ends_at']);
        });

        Schema::create('student_fee_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_fee_transaction_id')->constrained('student_fee_transactions')->restrictOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('awaiting_payment');
            $table->string('proof_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['student_fee_transaction_id', 'installment_number'], 'student_fee_payment_installment_unique');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_payments');
        Schema::dropIfExists('student_fee_transactions');

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('access_expires_at');
        });

        Schema::table('registration_payments', function (Blueprint $table): void {
            $table->dropColumn(['duration_years', 'installment_count']);
        });
    }
};