<?php

use App\Http\Controllers\ExamController;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Student;
use App\Repositories\ExamAttemptRepository;
use App\Repositories\ExamRepository;
use App\Services\ExamAttemptService;
use App\Services\ExamService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->attemptService = new ExamAttemptService(new ExamAttemptRepository());
    $this->examService = new ExamService(new ExamRepository());
});

function makeSecurityTestStudent(array $attributes = []): Student
{
    return Student::create(array_merge([
        'student_number' => 'SEC-' . uniqid(),
        'full_name' => 'Security Test Student',
        'is_active' => true,
    ], $attributes));
}

function makeSecurityTestExam(array $attributes = []): Exam
{
    return Exam::create(array_merge([
        'code' => 'SEC-' . strtoupper(substr(uniqid(), -8)),
        'title' => 'Security Test Exam',
        'duration_minutes' => 30,
        'max_attempts' => 1,
        'status' => 'published',
        'shuffle_questions' => true,
        'shuffle_options' => true,
        'show_result_immediately' => true,
        'show_correct_answers' => false,
    ], $attributes));
}

function makeSecurityTestQuestion(Exam $exam): ExamQuestion
{
    return ExamQuestion::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => '<p>Security test question</p>',
        'score' => 1,
        'sort_order' => 1,
    ]);
}

function assignSecurityTestParticipant(Exam $exam, Student $student, bool $active = true): ExamParticipant
{
    return ExamParticipant::create([
        'exam_id' => $exam->id,
        'student_id' => $student->id,
        'assigned_at' => now(),
        'is_active' => $active,
    ]);
}

test('a student who is not assigned cannot start an exam', function () {
    $exam = makeSecurityTestExam();
    $student = makeSecurityTestStudent();

    expect(fn () => $this->attemptService->start($exam, $student))
        ->toThrow(RuntimeException::class, 'peserta aktif');

    $this->assertDatabaseCount('exam_attempts', 0);
});

test('a student with an inactive participant record cannot start an exam', function () {
    $exam = makeSecurityTestExam();
    $student = makeSecurityTestStudent();
    assignSecurityTestParticipant($exam, $student, false);

    expect(fn () => $this->attemptService->start($exam, $student))
        ->toThrow(RuntimeException::class, 'peserta aktif');

    $this->assertDatabaseCount('exam_attempts', 0);
});

test('an inactive student cannot start an exam even when assigned', function () {
    $exam = makeSecurityTestExam();
    $student = makeSecurityTestStudent(['is_active' => false]);
    assignSecurityTestParticipant($exam, $student);

    expect(fn () => $this->attemptService->start($exam, $student))
        ->toThrow(RuntimeException::class, 'tidak aktif');

    $this->assertDatabaseCount('exam_attempts', 0);
});

test('an active participant gets one attempt and repeated starts resume it', function () {
    $exam = makeSecurityTestExam();
    $student = makeSecurityTestStudent();
    assignSecurityTestParticipant($exam, $student);
    makeSecurityTestQuestion($exam);

    $first = $this->attemptService->start($exam, $student);
    $second = $this->attemptService->start($exam, $student);

    expect($second->id)->toBe($first->id);
    $this->assertDatabaseCount('exam_attempts', 1);
});

test('a question from another exam cannot be removed through this exam URL', function () {
    $examA = makeSecurityTestExam();
    $examB = makeSecurityTestExam();
    $question = makeSecurityTestQuestion($examB);

    expect(fn () => $this->examService->removeQuestionFromExam($examA->id, $question->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('exam_questions', [
        'id' => $question->id,
        'exam_id' => $examB->id,
    ]);
});

test('questions cannot be changed after an attempt exists', function () {
    $exam = makeSecurityTestExam();
    $student = makeSecurityTestStudent();
    assignSecurityTestParticipant($exam, $student);
    $question = makeSecurityTestQuestion($exam);
    $this->attemptService->start($exam, $student);

    expect(fn () => $this->examService->removeQuestionFromExam($exam->id, $question->id))
        ->toThrow(RuntimeException::class, 'tidak dapat diubah');

    $this->assertDatabaseHas('exam_questions', ['id' => $question->id]);
});

test('bulk question removal is blocked after an attempt exists', function () {
    $exam = makeSecurityTestExam();
    $student = makeSecurityTestStudent();
    assignSecurityTestParticipant($exam, $student);
    $question = makeSecurityTestQuestion($exam);
    $this->attemptService->start($exam, $student);

    $response = (new ExamController($this->examService))->bulkDestroyQuestions($exam);

    expect($response->getStatusCode())->toBe(409);
    expect($response->getData(true)['status'])->toBeFalse();

    $this->assertDatabaseHas('exam_questions', ['id' => $question->id]);
});
