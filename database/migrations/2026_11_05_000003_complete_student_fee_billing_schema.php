<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->dropForeign(['registration_payment_id']);
            $table->string('package_type', 20)->default('semester')->after('duration_years');
            $table->unsignedTinyInteger('duration_months')->default(6)->after('package_type');
            $table->foreignId('registration_payment_id')->nullable()->change();
            $table->foreign('registration_payment_id')
                ->references('id')
                ->on('registration_payments')
                ->restrictOnDelete();
        });

        Schema::table('student_fee_payments', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('installment_number');
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_payments', function (Blueprint $table): void {
            $table->dropColumn('due_date');
        });

        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->dropForeign(['registration_payment_id']);
            $table->dropColumn(['package_type', 'duration_months']);
            $table->foreignId('registration_payment_id')->nullable(false)->change();
            $table->foreign('registration_payment_id')
                ->references('id')
                ->on('registration_payments')
                ->restrictOnDelete();
        });
    }
};
