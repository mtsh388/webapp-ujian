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
            $table->foreignId('registration_payment_id')->nullable()->change();
            $table->foreign('registration_payment_id')->references('id')->on('registration_payments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->dropForeign(['registration_payment_id']);
            $table->foreignId('registration_payment_id')->nullable(false)->change();
            $table->foreign('registration_payment_id')->references('id')->on('registration_payments')->restrictOnDelete();
        });
    }
};