<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_payments', function (Blueprint $table): void {
            $table->string('tuition_promo_code', 100)->nullable()->after('installment_count');
        });

        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->string('promo_code', 100)->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->dropColumn('promo_code');
        });

        Schema::table('registration_payments', function (Blueprint $table): void {
            $table->dropColumn('tuition_promo_code');
        });
    }
};