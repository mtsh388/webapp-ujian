<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use Illuminate\Database\Seeder;

class EducationLevelClassSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            'SD' => [4, 5, 6],
            'SMP' => [7, 8, 9],
            'SMA' => [10, 11, 12],
        ];

        foreach ($grades as $level => $numbers) {
            foreach ($numbers as $grade) {
                ClassRoom::updateOrCreate(
                    ['code' => "{$level}-{$grade}"],
                    [
                        'name' => "Kelas {$grade} {$level}",
                        'education_level' => $level,
                        'grade_level' => $grade,
                        'description' => "Kelas {$grade} jenjang {$level}",
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
