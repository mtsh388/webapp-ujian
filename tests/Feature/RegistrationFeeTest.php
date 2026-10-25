<?php

use App\Models\RegistrationFee;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = collect([
        'registration-fee.view',
        'registration-fee.create',
        'registration-fee.update',
        'registration-fee.delete',
    ])->map(fn ($name) => Permission::firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]));

    $adminRole = Role::firstOrCreate([
        'name' => 'Admin',
        'guard_name' => 'web',
    ]);
    $adminRole->syncPermissions($permissions);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin);
});

test('admin can view the registration fee master data', function () {
    $this->get(route('registration-fees.index'))
        ->assertOk()
        ->assertSee('Master Data Biaya')
        ->assertSee('Kelas 12')
        ->assertSee('Rp500.000');
});

test('admin can update an existing registration fee', function () {
    $fee = RegistrationFee::where('education_level', 'SMA')
        ->where('grade_level', 11)
        ->firstOrFail();

    $this->put(route('registration-fees.update', $fee), [
        'fee_type' => 'registration',
        'education_level' => 'SMA',
        'grade_level' => 11,
        'amount' => 425000,
        'is_active' => 1,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'id' => $fee->id,
        'education_level' => 'SMA',
        'grade_level' => 11,
        'amount' => 425000,
        'is_active' => true,
    ]);
});

test('admin cannot assign a grade outside its education level', function () {
    $fee = RegistrationFee::where('education_level', 'SMP')
        ->where('grade_level', 8)
        ->firstOrFail();

    $this->from(route('registration-fees.edit', $fee))
        ->put(route('registration-fees.update', $fee), [
            'fee_type' => 'registration',
            'education_level' => 'SMP',
            'grade_level' => 12,
            'amount' => 300000,
            'is_active' => 1,
        ])
        ->assertRedirect(route('registration-fees.edit', $fee))
        ->assertSessionHasErrors('grade_level');
});


test('admin can create a general fee without targeting a class', function () {
    $this->post(route('registration-fees.store'), [
        'fee_type' => 'books',
        'name' => 'Modul Pembelajaran',
        'description' => 'Biaya modul untuk siswa',
        'billing_period' => 'semester',
        'education_level' => '',
        'grade_level' => '',
        'amount' => 150000,
        'is_active' => 1,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'fee_type' => 'books',
        'name' => 'Modul Pembelajaran',
        'billing_period' => 'semester',
        'education_level' => null,
        'grade_level' => null,
        'amount' => 150000,
        'is_active' => true,
    ]);
});

