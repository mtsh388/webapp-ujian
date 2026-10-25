<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->boolean('discount_enabled')->default(false)->after('amount');
            $table->string('promo_code', 100)->nullable()->after('discount_enabled');
            $table->timestamp('promo_starts_at')->nullable()->after('promo_code');
            $table->timestamp('promo_ends_at')->nullable()->after('promo_starts_at');
            $table->string('discount_type', 20)->nullable()->after('promo_ends_at');
            $table->unsignedBigInteger('discount_value')->nullable()->after('discount_type');
            $table->unsignedTinyInteger('max_installments')->default(1)->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->dropColumn([
                'discount_enabled',
                'promo_code',
                'promo_starts_at',
                'promo_ends_at',
                'discount_type',
                'discount_value',
                'max_installments',
            ]);
        });
    }
};
