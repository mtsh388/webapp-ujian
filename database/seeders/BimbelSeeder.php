<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamQuestionOption;
use App\Models\ParentModel;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BimbelSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@bimbel.test'],
            ['name' => 'Administrator', 'password' => Hash::make('password')]
        );

        $tentor = User::firstOrCreate(
            ['email' => 'tentor@bimbel.test'],
            ['name' => 'Tentor Matematika', 'password' => Hash::make('password')]
        );

        $parentUser1 = User::firstOrCreate(
            ['email' => 'budi.orangtua@bimbel.test'],
            ['name' => 'Budi Santoso', 'password' => Hash::make('password')]
        );

        $parentUser2 = User::firstOrCreate(
            ['email' => 'siti.orangtua@bimbel.test'],
            ['name' => 'Siti Rahma', 'password' => Hash::make('password')]
        );

        $class10 = ClassRoom::firstOrCreate(['code' => 'K10-REG'], [
            'name' => 'Kelas 10 Reguler',
            'code' => 'K10-REG',
            'education_level' => 'SMA',
            'grade_level' => 10,
            'description' => 'Kelas reguler tingkat 10',
            'is_active' => true,
        ]);

        $class11 = ClassRoom::firstOrCreate(['code' => 'K11-REG'], [
            'name' => 'Kelas 11 Reguler',
            'code' => 'K11-REG',
            'education_level' => 'SMA',
            'grade_level' => 11,
            'description' => 'Kelas reguler tingkat 11',
            'is_active' => true,
        ]);

        $subjects = [
            [
                'name' => 'Matematika',
                'code' => 'SMA-10-MAT-001',
                'education_level' => 'SMA',
                'topic' => 'Operasi Hitung Dasar',
                'grade_level' => 10,
            ],
            [
                'name' => 'Bahasa Indonesia',
                'code' => 'SMA-10-BI-001',
                'education_level' => 'SMA',
                'topic' => 'Kata Benda',
                'grade_level' => 10,
            ],
            [
                'name' => 'Bahasa Inggris',
                'code' => 'SMA-10-BI-002',
                'education_level' => 'SMA',
                'topic' => 'Vocabulary Dasar',
                'grade_level' => 10,
            ],
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate(['code' => $subjectData['code']], [
                'name' => $subjectData['name'],
                'code' => $subjectData['code'],
                'education_level' => $subjectData['education_level'],
                'topic' => $subjectData['topic'],
                'grade_level' => $subjectData['grade_level'],
                'is_active' => true,
            ]);
        }

        $students = [];
        $studentUsers = [];

        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "siswa{$i}@bimbel.test"],
                ['name' => "Siswa {$i}", 'password' => Hash::make('password')]
            );

            $student = Student::firstOrCreate(['user_id' => $user->id], [
                'registration_type' => 'paid',
                'class_id' => $i <= 3 ? $class10->id : $class11->id,
                'student_number' => 'S' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'full_name' => "Siswa {$i}",
                'gender' => $i % 2 === 0 ? 'Perempuan' : 'Laki-laki',
                'phone' => '08123456789' . $i,
                'address' => 'Jakarta',
                'is_active' => true,
            ]);

            $students[] = $student;
            $studentUsers[] = $user;
        }

        $parents = [];

        $parents[] = ParentModel::firstOrCreate(['user_id' => $parentUser1->id], [
            'full_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);

        $parents[] = ParentModel::firstOrCreate(['user_id' => $parentUser2->id], [
            'full_name' => 'Siti Rahma',
            'phone' => '081234567891',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);

        $students[0]->parents()->syncWithoutDetaching([$parents[0]->id => [
            'relationship' => 'Ayah',
            'is_primary' => true,
        ]]);

        $students[1]->parents()->syncWithoutDetaching([$parents[0]->id => [
            'relationship' => 'Ayah',
            'is_primary' => true,
        ]]);

        $students[2]->parents()->syncWithoutDetaching([$parents[1]->id => [
            'relationship' => 'Ibu',
            'is_primary' => true,
        ]]);

        $matematika = Subject::where('name', 'Matematika')->first();
        $bahasaIndonesia = Subject::where('name', 'Bahasa Indonesia')->first();

        $questions = [
            [
                'subject_id' => $matematika->id,
                'code' => 'MTK-001',
                'type' => 'multiple_choice',
                'question' => 'Hasil dari 2 + 2 adalah?',
                'explanation' => 'Hasil penjumlahan 2 + 2 adalah 4.',
                'difficulty' => 'easy',
                'options' => [
                    ['key' => 'A', 'text' => '2', 'correct' => false],
                    ['key' => 'B', 'text' => '3', 'correct' => false],
                    ['key' => 'C', 'text' => '4', 'correct' => true],
                    ['key' => 'D', 'text' => '5', 'correct' => false],
                ],
            ],
            [
                'subject_id' => $matematika->id,
                'code' => 'MTK-002',
                'type' => 'multiple_choice',
                'question' => 'Hasil dari 5 × 6 adalah?',
                'explanation' => '5 dikali 6 menghasilkan 30.',
                'difficulty' => 'easy',
                'options' => [
                    ['key' => 'A', 'text' => '25', 'correct' => false],
                    ['key' => 'B', 'text' => '30', 'correct' => true],
                    ['key' => 'C', 'text' => '35', 'correct' => false],
                    ['key' => 'D', 'text' => '40', 'correct' => false],
                ],
            ],
            [
                'subject_id' => $matematika->id,
                'code' => 'MTK-003',
                'type' => 'true_false',
                'question' => 'Bilangan 10 merupakan bilangan genap.',
                'explanation' => 'Bilangan yang habis dibagi 2 merupakan bilangan genap.',
                'difficulty' => 'easy',
                'options' => [
                    ['key' => 'A', 'text' => 'Benar', 'correct' => true],
                    ['key' => 'B', 'text' => 'Salah', 'correct' => false],
                ],
            ],
            [
                'subject_id' => $bahasaIndonesia->id,
                'code' => 'BIN-001',
                'type' => 'multiple_choice',
                'question' => 'Manakah yang merupakan kata benda?',
                'explanation' => 'Buku merupakan kata benda.',
                'difficulty' => 'easy',
                'options' => [
                    ['key' => 'A', 'text' => 'Buku', 'correct' => true],
                    ['key' => 'B', 'text' => 'Membaca', 'correct' => false],
                    ['key' => 'C', 'text' => 'Indah', 'correct' => false],
                    ['key' => 'D', 'text' => 'Cepat', 'correct' => false],
                ],
            ],
            [
                'subject_id' => $bahasaIndonesia->id,
                'code' => 'BIN-002',
                'type' => 'true_false',
                'question' => 'Bahasa Indonesia merupakan bahasa resmi negara Indonesia.',
                'explanation' => 'Bahasa Indonesia merupakan bahasa resmi negara Indonesia.',
                'difficulty' => 'easy',
                'options' => [
                    ['key' => 'A', 'text' => 'Benar', 'correct' => true],
                    ['key' => 'B', 'text' => 'Salah', 'correct' => false],
                ],
            ],
        ];

        foreach ($questions as $questionData) {
            $options = $questionData['options'];

            unset($questionData['options']);

            $question = QuestionBank::firstOrCreate(['code' => $questionData['code']], [
                ...$questionData,
                'default_score' => 1,
                'is_active' => true,
            ]);

            foreach ($options as $index => $option) {
                QuestionOption::firstOrCreate([
                    'question_bank_id' => $question->id,
                    'option_key' => $option['key'],
                ], [
                    'option_key' => $option['key'],
                    'option_text' => $option['text'],
                    'is_correct' => $option['correct'],
                    'sort_order' => $index + 1,
                ]);
            }
        }

        $exam = Exam::firstOrCreate(['code' => 'UJ-MTK-001'], [
            'subject_id' => $matematika->id,
            'code' => 'UJ-MTK-001',
            'title' => 'Ujian Matematika Dasar',
            'description' => 'Ujian Matematika Dasar untuk kelas reguler.',
            'duration_minutes' => 30,
            'start_at' => now(),
            'end_at' => now()->addDays(7),
            'passing_score' => 75,
            'max_attempts' => 1,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_result_immediately' => true,
            'show_correct_answers' => true,
            'status' => 'published',
        ]);

        $bankQuestions = QuestionBank::where('subject_id', $matematika->id)
            ->with('options')
            ->orderBy('id')
            ->get();

        foreach ($bankQuestions as $index => $bankQuestion) {
            $examQuestion = ExamQuestion::firstOrCreate([
                'exam_id' => $exam->id,
                'question_bank_id' => $bankQuestion->id,
            ], [
                'question_bank_id' => $bankQuestion->id,
                'type' => $bankQuestion->type,
                'question' => $bankQuestion->question,
                'explanation' => $bankQuestion->explanation,
                'score' => $bankQuestion->default_score,
                'sort_order' => $index + 1,
            ]);

            foreach ($bankQuestion->options as $option) {
                ExamQuestionOption::firstOrCreate([
                    'exam_question_id' => $examQuestion->id,
                    'option_key' => $option->option_key,
                ], [
                    'option_key' => $option->option_key,
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                    'sort_order' => $option->sort_order,
                ]);
            }
        }

        foreach ($students as $student) {
            ExamParticipant::firstOrCreate([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ], [
                'student_id' => $student->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        }
        $keuangan = User::firstOrCreate(
            ['email' => 'keuangan@bimbel.test'],
            ['name' => 'Staff Keuangan', 'password' => Hash::make('password')]
        );

        $admin->syncRoles(['Admin']);
        $keuangan->syncRoles(['Keuangan']);

        $tentor->syncRoles(['Tentor']);

        $parentUser1->syncRoles(['Orang Tua']);
        $parentUser2->syncRoles(['Orang Tua']);

        foreach ($studentUsers as $studentUser) {
            $studentUser->syncRoles(['Siswa']);
        }
    }

}
