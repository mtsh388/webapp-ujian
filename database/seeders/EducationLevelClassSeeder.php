<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use Illuminate\Database\Seeder;

class EducationLevelClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['education_level' => 'SD', 'grade_level' => 4, 'name' => 'Kelas 4 SD', 'code' => 'SD-4'],
            ['education_level' => 'SD', 'grade_level' => 5, 'name' => 'Kelas 5 SD', 'code' => 'SD-5'],
            ['education_level' => 'SD', 'grade_level' => 6, 'name' => 'Kelas 6 SD', 'code' => 'SD-6'],
            ['education_level' => 'SMP', 'grade_level' => 7, 'name' => 'Kelas 7 SMP', 'code' => 'SMP-7'],
            ['education_level' => 'SMP', 'grade_level' => 8, 'name' => 'Kelas 8 SMP', 'code' => 'SMP-8'],
            ['education_level' => 'SMP', 'grade_level' => 9, 'name' => 'Kelas 9 SMP', 'code' => 'SMP-9'],
            ['education_level' => 'SMA', 'grade_level' => 10, 'name' => 'Kelas 10 SMA', 'code' => 'SMA-10'],
            ['education_level' => 'SMA', 'grade_level' => 11, 'name' => 'Kelas 11 SMA', 'code' => 'SMA-11'],
            ['education_level' => 'SMA', 'grade_level' => 12, 'name' => 'Kelas 12 SMA', 'code' => 'SMA-12'],
        ];

        foreach ($classes as $data) {
            $classRoom = ClassRoom::withTrashed()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'education_level' => $data['education_level'],
                    'grade_level' => $data['grade_level'],
                    'description' => 'Kelas ' . $data['grade_level'] . ' jenjang ' . $data['education_level'],
                    'is_active' => true,
                ]
            );

            if ($classRoom->trashed()) {
                $classRoom->restore();
            }
        }
    }
}
