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


test('active student accounts can authenticate while the student profile is inactive', function () {
    $role = Role::firstOrCreate([
        'name' => 'Siswa',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $user->assignRole($role);

    Student::create([
        'user_id' => $user->id,
        'student_number' => 'QSEE-LOGIN-INACTIVE-001',
        'full_name' => 'Siswa Belum Aktif',
        'is_active' => false,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('students.profile.edit'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->is_active)->toBeTrue();
    expect($user->student->fresh()->is_active)->toBeFalse();
});

test('deactivating a student account also deactivates the student without auto-reactivating the student later', function () {
    $viewPermission = Permission::firstOrCreate([
        'name' => 'student.view',
        'guard_name' => 'web',
    ]);

    $updatePermission = Permission::firstOrCreate([
        'name' => 'student.update',
        'guard_name' => 'web',
    ]);

    $admin = User::factory()->create();
    $admin->forceFill([
        'must_change_password' => false,
    ])->save();
    $admin->givePermissionTo([$viewPermission, $updatePermission]);

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
