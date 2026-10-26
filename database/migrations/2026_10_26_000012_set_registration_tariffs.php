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

        $classes = DB::table('classes')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereIn('education_level', ['SD', 'SMP', 'SMA'])
            ->whereNotNull('grade_level')
            ->get(['education_level', 'grade_level']);

        foreach ($classes as $class) {
            $amount = match ($class->education_level) {
                'SD' => 200000,
                'SMP' => 300000,
                'SMA' => match ((int) $class->grade_level) {
                    12 => 500000,
                    10, 11 => 400000,
                    default => null,
                },
                default => null,
            };

            if ($amount === null) {
                continue;
            }

            $fee = DB::table('registration_fees')
                ->where('fee_type', 'registration')
                ->where('education_level', $class->education_level)
                ->where('grade_level', $class->grade_level)
                ->first();

            if ($fee) {
                DB::table('registration_fees')->where('id', $fee->id)->update([
                    'amount' => $amount,
                    'updated_at' => now(),
                ]);
                continue;
            }

            DB::table('registration_fees')->insert([
                'fee_type' => 'registration',
                'fee_type_label' => 'Pendaftaran',
                'name' => 'Pendaftaran ' . $class->education_level . ' Kelas ' . $class->grade_level,
                'description' => null,
                'billing_period' => 'once',
                'education_level' => $class->education_level,
                'grade_level' => $class->grade_level,
                'amount' => $amount,
                'is_active' => true,
                'discount_enabled' => false,
                'promo_code' => null,
                'promo_starts_at' => null,
                'promo_ends_at' => null,
                'discount_type' => null,
                'discount_value' => null,
                'max_installments' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
