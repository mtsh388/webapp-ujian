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
        'fee_type' => 'Pendaftaran',
        'education_level' => 'SMA',
        'grade_level' => 11,
        'amount' => 425000,
        'is_active' => 1,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'id' => $fee->id,
        'fee_type' => 'registration',
        'fee_type_label' => 'Pendaftaran',
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
            'fee_type' => 'Pendaftaran',
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
        'fee_type' => 'Buku dan Modul',
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
        'fee_type_label' => 'Buku dan Modul',
        'name' => 'Modul Pembelajaran',
        'billing_period' => 'semester',
        'education_level' => null,
        'grade_level' => null,
        'amount' => 150000,
        'is_active' => true,
    ]);
});

test('admin can create a fee with any free-text type', function () {
    $this->post(route('registration-fees.store'), [
        'fee_type' => 'Laboratorium Komputer',
        'name' => 'Praktikum Komputer',
        'description' => 'Biaya praktikum khusus',
        'billing_period' => 'once',
        'education_level' => 'SMP',
        'grade_level' => '',
        'amount' => 75000,
        'is_active' => 1,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'fee_type' => 'laboratorium-komputer',
        'fee_type_label' => 'Laboratorium Komputer',
        'name' => 'Praktikum Komputer',
        'education_level' => 'SMP',
        'grade_level' => null,
        'amount' => 75000,
        'is_active' => true,
    ]);

    $this->get(route('registration-fees.index'))
        ->assertOk()
        ->assertSee('Laboratorium Komputer');
});

test('admin can reuse a free-text fee type', function () {
    RegistrationFee::create([
        'fee_type' => 'laboratorium-komputer',
        'fee_type_label' => 'Laboratorium Komputer',
        'name' => 'Praktikum Komputer',
        'billing_period' => 'once',
        'education_level' => 'SMP',
        'grade_level' => 7,
        'amount' => 75000,
        'is_active' => true,
    ]);

    $this->get(route('registration-fees.create'))
        ->assertOk()
        ->assertSee('Jenis Biaya')
        ->assertSee('type="text"', false);

    $this->post(route('registration-fees.store'), [
        'fee_type' => 'Laboratorium Komputer',
        'name' => 'Pemeliharaan Laboratorium',
        'billing_period' => 'yearly',
        'education_level' => '',
        'grade_level' => '',
        'amount' => 25000,
        'is_active' => 1,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'fee_type' => 'laboratorium-komputer',
        'fee_type_label' => 'Laboratorium Komputer',
        'name' => 'Pemeliharaan Laboratorium',
        'amount' => 25000,
        'education_level' => null,
        'grade_level' => null,
    ]);
});
