<?php

use App\Models\ClassRoom;
use App\Models\RegistrationPayment;
use App\Models\RegistrationFee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate([
        'name' => 'Siswa',
        'guard_name' => 'web',
    ]);
    Role::firstOrCreate([
        'name' => 'Orang Tua',
        'guard_name' => 'web',
    ]);
});

test('registration page is available to guests', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Trial')
        ->assertSee('Berbayar')
        ->assertSee('id="education_level"', false)
        ->assertSee('id="class_id"', false)
        ->assertSee('id="registration_fee_display"', false)
        ->assertSee('readonly', false)
        ->assertSee('Master Data Biaya QSEE')
        ->assertSee('function updateFee()', false)
        ->assertSee('data-fee=', false);
});

test('a visitor can register a trial student account', function () {
    $this->post('/register', [
        'registration_type' => 'trial',
        'email' => 'siswa@example.com',
        'password' => 'password123',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $user = User::where('email', 'siswa@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Siswa')
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->hasRole('Siswa'))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'email' => 'siswa@example.com',
    ]);

    $this->assertDatabaseMissing('students', [
        'user_id' => $user->id,
    ]);
});

test('registration requires a valid unique email and password', function () {
    User::factory()->create([
        'email' => 'siswa@example.com',
    ]);

    $this->from('/register')
        ->post('/register', [
            'registration_type' => 'trial',
            'email' => 'siswa@example.com',
            'password' => 'short',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors(['email', 'password']);
});

test('paid registration stores student parent and pending registration payment at server tariff', function () {
    Storage::fake('local');

    $class = ClassRoom::create([
        'name' => 'Kelas 12 SMA',
        'code' => 'SMA-12-REG',
        'education_level' => 'SMA',
        'grade_level' => 12,
        'is_active' => true,
    ]);

    RegistrationFee::where('education_level', 'SMA')
        ->where('grade_level', 12)
        ->update(['amount' => 500000, 'is_active' => true]);

    $this->post('/register', [
        'registration_type' => 'paid',
        'email' => 'siswa.paid@example.com',
        'password' => 'password123',
        'full_name' => 'Nama Siswa Sesuai Data',
        'school_name' => 'SMA Negeri 1',
        'education_level' => 'SMA',
        'class_id' => $class->id,
        'gender' => 'Laki-laki',
        'phone' => '081234567890',
        'parent_phone' => '081234567891',
        'parent_full_name' => 'Nama Orang Tua',
        'parent_email' => 'orangtua@example.com',
        'parent_address' => 'Jakarta',
        'amount' => 1,
        'registration_fee_display' => 'Rp1',
        'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Selamat, Anda sudah bergabung. Silakan datang ke QSEE untuk melakukan proses selanjutnya.');

    $user = User::where('email', 'siswa.paid@example.com')->firstOrFail();
    $student = Student::where('user_id', $user->id)->firstOrFail();
    $payment = RegistrationPayment::where('student_id', $student->id)->firstOrFail();

    expect($user->name)->toBe('Nama Siswa Sesuai Data')
        ->and($student->full_name)->toBe('Nama Siswa Sesuai Data')
        ->and($student->school_name)->toBe('SMA Negeri 1')
        ->and($student->education_level)->toBe('SMA')
        ->and($student->class_id)->toBe($class->id)
        ->and($payment->amount)->toBe(500000)
        ->and($payment->status)->toBe('pending_verification')
        ->and($payment->payment_type)->toBe('registration')
        ->and($payment->promo_code)->toBeNull()
        ->and($payment->installment_status)->toBe('not_allowed')
        ->and($user->is_active)->toBeTrue()
        ->and($user->hasRole('Siswa'))->toBeTrue();

    Storage::disk('local')->assertExists($payment->proof_path);

    $parentUser = User::where('email', 'orangtua@example.com')->firstOrFail();
    $this->assertDatabaseHas('parents', [
        'user_id' => $parentUser->id,
        'full_name' => 'Nama Orang Tua',
    ]);
    $this->assertDatabaseHas('student_parent', [
        'student_id' => $student->id,
        'parent_id' => $parentUser->parentProfile->id,
        'is_primary' => true,
    ]);
});

test('paid registration rejects promo codes even when submitted directly', function () {
    $this->from('/register')
        ->post('/register', [
            'registration_type' => 'trial',
            'email' => 'promo@example.com',
            'password' => 'password123',
            'promo_code' => 'HEMAT50',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors('registration_type');

    $this->assertDatabaseMissing('users', [
        'email' => 'promo@example.com',
    ]);
});

test('paid registration rejects a client-supplied installment field', function () {
    $this->from('/register')
        ->post('/register', [
            'registration_type' => 'trial',
            'email' => 'cicilan@example.com',
            'password' => 'password123',
            'installments' => 2,
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors('registration_type');

    $this->assertDatabaseMissing('users', [
        'email' => 'cicilan@example.com',
    ]);
});

test('paid registration applies the configured tariff for SD SMP and SMA grades 10 and 11', function () {
    Storage::fake('local');

    $tariffs = [
        ['level' => 'SD', 'grade' => 6, 'amount' => 200000],
        ['level' => 'SMP', 'grade' => 9, 'amount' => 300000],
        ['level' => 'SMA', 'grade' => 10, 'amount' => 400000],
        ['level' => 'SMA', 'grade' => 11, 'amount' => 400000],
    ];

    foreach ($tariffs as $index => $tariff) {
        $class = ClassRoom::create([
            'name' => 'Kelas ' . $tariff['grade'] . ' ' . $tariff['level'],
            'code' => $tariff['level'] . '-' . $tariff['grade'] . '-TARIF',
            'education_level' => $tariff['level'],
            'grade_level' => $tariff['grade'],
            'is_active' => true,
        ]);

        RegistrationFee::where('education_level', $tariff['level'])
            ->where('grade_level', $tariff['grade'])
            ->update(['amount' => $tariff['amount'], 'is_active' => true]);

        $email = 'siswa.tarif' . $index . '@example.com';

        $this->post('/register', [
            'registration_type' => 'paid',
            'email' => $email,
            'password' => 'password123',
            'full_name' => 'Siswa Tarif ' . $index,
            'school_name' => 'Sekolah Contoh',
            'education_level' => $tariff['level'],
            'class_id' => $class->id,
            'phone' => '081234567890',
            'parent_phone' => '081234567891',
            'parent_full_name' => 'Orang Tua Tarif ' . $index,
            'parent_email' => 'orangtua.tarif' . $index . '@example.com',
            'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('login'));

        $student = Student::whereHas('user', fn ($query) => $query->where('email', $email))->firstOrFail();

        expect(RegistrationPayment::where('student_id', $student->id)->value('amount'))
            ->toBe($tariff['amount']);
    }
});
