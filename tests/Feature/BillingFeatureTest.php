<?php

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\Student;
use App\Models\User;
use App\Repositories\ExamAttemptRepository;
use App\Services\ExamAttemptService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeBillingAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

function makeBillingStudent(): Student
{
    return Student::query()->create([
        'student_number' => 'BILL-' . strtoupper(substr(uniqid(), -8)),
        'full_name' => 'Siswa Pengujian Tagihan',
        'is_active' => true,
    ]);
}

function makeBillingInvoice(Student $student, array $attributes = []): BillingInvoice
{
    return BillingInvoice::query()->create(array_merge([
        'student_id' => $student->id,
        'title' => 'Tagihan Semester',
        'period_type' => 'semester',
        'period_label' => 'Semester Ganjil 2026/2027',
        'amount' => 1000000,
        'due_date' => now()->addDays(14)->toDateString(),
        'payment_status' => 'unpaid',
    ], $attributes));
}

test('partial verified payment updates invoice status and only verified payments count as income', function () {
    $admin = makeBillingAdmin();
    $student = makeBillingStudent();
    $invoice = makeBillingInvoice($student);

    $payment = BillingPayment::query()->create([
        'invoice_id' => $invoice->id,
        'amount' => 300000,
        'method' => 'transfer',
        'paid_at' => now()->toDateString(),
        'status' => 'pending',
        'recorded_by' => $admin->id,
    ]);

    expect((float) $invoice->fresh()->verified_amount)->toBe(0.0);

    $this->actingAs($admin)->post(route('billing.payments.verify', $payment), [
        'status' => 'verified',
    ])->assertRedirect(route('billing.show', $invoice));

    expect($invoice->fresh()->payment_status)->toBe('partial')
        ->and((float) $invoice->fresh()->verified_amount)->toBe(300000.0);

    $this->actingAs($admin)->post(route('billing.payments.verify', $payment), [
        'status' => 'verified',
    ])->assertSessionHasErrors('status');
});

test('fully paid invoice status becomes paid after all installments are verified', function () {
    $admin = makeBillingAdmin();
    $student = makeBillingStudent();
    $invoice = makeBillingInvoice($student);

    foreach ([400000, 600000] as $amount) {
        $payment = BillingPayment::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
            'status' => 'pending',
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('billing.payments.verify', $payment), [
            'status' => 'verified',
        ])->assertRedirect();
    }

    expect($invoice->fresh()->payment_status)->toBe('paid')
        ->and((float) $invoice->fresh()->verified_amount)->toBe(1000000.0);
});

test('rejected payment does not reduce the outstanding invoice amount', function () {
    $admin = makeBillingAdmin();
    $student = makeBillingStudent();
    $invoice = makeBillingInvoice($student);
    $payment = BillingPayment::query()->create([
        'invoice_id' => $invoice->id,
        'amount' => 250000,
        'method' => 'transfer',
        'paid_at' => now()->toDateString(),
        'status' => 'pending',
        'recorded_by' => $admin->id,
    ]);

    $this->actingAs($admin)->post(route('billing.payments.verify', $payment), [
        'status' => 'rejected',
        'verification_note' => 'Bukti pembayaran tidak valid',
    ])->assertRedirect();

    expect($invoice->fresh()->payment_status)->toBe('unpaid')
        ->and((float) $invoice->fresh()->outstanding_amount)->toBe(1000000.0);
});


test('an unpaid invoice does not prevent an eligible student from starting an exam', function () {
    $student = makeBillingStudent();
    makeBillingInvoice($student);

    $exam = Exam::query()->create([
        'code' => 'BILL-EXAM-' . strtoupper(substr(uniqid(), -6)),
        'title' => 'Ujian dengan Tagihan Belum Lunas',
        'duration_minutes' => 30,
        'max_attempts' => 1,
        'status' => 'published',
        'shuffle_questions' => true,
        'shuffle_options' => true,
        'show_result_immediately' => true,
        'show_correct_answers' => false,
    ]);

    ExamParticipant::query()->create([
        'exam_id' => $exam->id,
        'student_id' => $student->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    ExamQuestion::query()->create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => '<p>Soal pengujian</p>',
        'score' => 1,
        'sort_order' => 1,
    ]);

    $attempt = (new ExamAttemptService(new ExamAttemptRepository()))->start($exam, $student);

    expect($attempt->status)->toBe('in_progress');
    expect($student->fresh()->is_active)->toBeTrue();
});
