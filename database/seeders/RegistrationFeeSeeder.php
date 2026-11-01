<?php

namespace Database\Seeders;

use App\Models\RegistrationFee;
use Illuminate\Database\Seeder;

class RegistrationFeeSeeder extends Seeder
{
    public function run(): void
    {
        $fees = [
            'SD' => [1 => 200000, 2 => 200000, 3 => 200000, 4 => 200000, 5 => 200000, 6 => 200000],
            'SMP' => [7 => 300000, 8 => 300000, 9 => 300000],
            'SMA' => [10 => 400000, 11 => 400000, 12 => 500000],
        ];

        foreach ($fees as $educationLevel => $grades) {
            foreach ($grades as $gradeLevel => $amount) {
                RegistrationFee::updateOrCreate(
                    [
                        'fee_type' => 'registration',
                        'education_level' => $educationLevel,
                        'grade_level' => $gradeLevel,
                    ],
                    [
                        'fee_type_label' => 'Pendaftaran',
                        'name' => 'Pendaftaran Siswa Baru',
                        'description' => 'Biaya pendaftaran siswa baru jenjang ' . $educationLevel . ' kelas ' . $gradeLevel,
                        'amount' => $amount,
                        'is_active' => true,
                        'discount_enabled' => false,
                        'promo_code' => null,
                        'promo_starts_at' => null,
                        'promo_ends_at' => null,
                        'discount_type' => null,
                        'discount_value' => null,
                        'max_installments' => 1,
                    ]
                );
            }
        }
    }
}
