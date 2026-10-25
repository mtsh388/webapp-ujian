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
        ->assertSee('Master Data Biaya Pendaftaran')
        ->assertSee('Kelas 12')
        ->assertSee('Rp500.000');
});

test('admin can update an existing registration fee', function () {
    $fee = RegistrationFee::where('education_level', 'SMA')
        ->where('grade_level', 11)
        ->firstOrFail();

    $this->put(route('registration-fees.update', $fee), [
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
            'education_level' => 'SMP',
            'grade_level' => 12,
            'amount' => 300000,
            'is_active' => 1,
        ])
        ->assertRedirect(route('registration-fees.edit', $fee))
        ->assertSessionHasErrors('grade_level');
});
