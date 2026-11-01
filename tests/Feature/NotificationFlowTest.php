<?php

use App\Models\ClassRoom;
use App\Models\RegistrationFee;
use App\Models\User;
use App\Notifications\StudentClassPromotedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Admin', 'Keuangan', 'Siswa', 'Orang Tua'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }
});

test('trial registration notifies active admins through database and mail channels', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('Admin');
    $finance = User::factory()->create(['is_active' => true]);
    $finance->assignRole('Keuangan');

    $this->post('/register', [
        'registration_type' => 'trial',
        'email' => 'trial.notification@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('login'));

    Notification::assertSentTo($admin, StudentClassPromotedNotification::class);
    Notification::assertNotSentTo($finance, StudentClassPromotedNotification::class);
});

test('paid registration notifies active admins and finance users', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('Admin');
    $finance = User::factory()->create(['is_active' => true]);
    $finance->assignRole('Keuangan');

    $class = ClassRoom::create([
        'name' => 'Kelas 10 SMA Notifikasi',
        'code' => 'SMA-10-NOTIF',
        'education_level' => 'SMA',
        'grade_level' => 10,
        'registration_type' => 'paid',
        'is_active' => true,
    ]);

    RegistrationFee::create([
        'fee_type' => 'registration',
        'fee_type_label' => 'Pendaftaran',
        'name' => 'Pendaftaran',
        'billing_period' => 'once',
        'education_level' => 'SMA',
        'grade_level' => 10,
        'amount' => 400000,
        'is_active' => true,
        'discount_enabled' => false,
        'max_installments' => 1,
    ]);

    $this->post('/register', [
        'registration_type' => 'paid',
        'email' => 'paid.notification@example.com',
        'password' => 'password123',
        'full_name' => 'Siswa Notifikasi',
        'school_name' => 'SMA Contoh',
        'education_level' => 'SMA',
        'class_id' => $class->id,
        'phone' => '081234567890',
        'parent_phone' => '081234567891',
        'parent_full_name' => 'Orang Tua Notifikasi',
        'parent_email' => 'parent.notification@example.com',
    ])->assertRedirect();

    Notification::assertSentTo($admin, StudentClassPromotedNotification::class);
    Notification::assertSentTo($finance, StudentClassPromotedNotification::class);
});
