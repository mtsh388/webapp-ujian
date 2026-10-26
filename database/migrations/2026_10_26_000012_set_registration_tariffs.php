<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('registration_fees')
            ->where('fee_type', 'registration')
            ->where('education_level', 'SD')
            ->update(['amount' => 200000]);

        DB::table('registration_fees')
            ->where('fee_type', 'registration')
            ->where('education_level', 'SMP')
            ->update(['amount' => 300000]);

        DB::table('registration_fees')
            ->where('fee_type', 'registration')
            ->where('education_level', 'SMA')
            ->whereIn('grade_level', [10, 11])
            ->update(['amount' => 400000]);

        DB::table('registration_fees')
            ->where('fee_type', 'registration')
            ->where('education_level', 'SMA')
            ->where('grade_level', 12)
            ->update(['amount' => 500000]);
    }

    public function down(): void
    {
    }
};
