<?php

use App\Models\Student;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('inactive student accounts cannot authenticate', function () {
    $role = Role::firstOrCreate([
        'name' => 'Siswa',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create([
        'is_active' => false,
    ]);
    $user->assignRole($role);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('deactivating a student account also deactivates the student without auto-reactivating the student later', function () {
    $permission = Permission::firstOrCreate([
        'name' => 'student.update',
        'guard_name' => 'web',
    ]);

    $admin = User::factory()->create();
    $admin->forceFill([
        'must_change_password' => false,
    ])->save();
    $admin->givePermissionTo($permission);

    $studentRole = Role::firstOrCreate([
        'name' => 'Siswa',
        'guard_name' => 'web',
    ]);

    $studentUser = User::factory()->create([
        'is_active' => true,
    ]);
    $studentUser->assignRole($studentRole);

    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_number' => 'QSEE-STATUS-001',
        'full_name' => 'Siswa Uji Status',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patchJson(route('students.account-status', $student), [
            'account_is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('account_is_active', false)
        ->assertJsonPath('is_active', false);

    expect($studentUser->fresh()->is_active)->toBeFalse();
    expect($student->fresh()->is_active)->toBeFalse();

    $this->patchJson(route('students.status', $student), [
        'is_active' => true,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('is_active');

    $this->patchJson(route('students.account-status', $student), [
        'account_is_active' => true,
    ])->assertOk()
        ->assertJsonPath('account_is_active', true)
        ->assertJsonPath('is_active', false);

    expect($studentUser->fresh()->is_active)->toBeTrue();
    expect($student->fresh()->is_active)->toBeFalse();
});
