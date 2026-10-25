<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->string('fee_type', 30)->default('registration')->after('id');
            $table->string('name')->nullable()->after('fee_type');
            $table->text('description')->nullable()->after('name');
            $table->string('billing_period', 20)->nullable()->after('description');
            $table->dropUnique('registration_fees_education_level_grade_level_unique');
        });

        DB::table('registration_fees')->update([
            'fee_type' => 'registration',
            'name' => 'Biaya Pendaftaran',
        ]);

        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->string('education_level', 10)->nullable()->change();
            $table->unsignedTinyInteger('grade_level')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('registration_fees')->where('fee_type', '!=', 'registration')->delete();

        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->unsignedTinyInteger('grade_level')->nullable(false)->change();
            $table->string('education_level', 10)->nullable(false)->change();
            $table->dropColumn(['fee_type', 'name', 'description', 'billing_period']);
            $table->unique(['education_level', 'grade_level']);
        });
    }
};
