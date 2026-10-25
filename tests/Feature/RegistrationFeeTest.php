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
        ->assertSee('serverSide: true');

    $this->getJson(route('registration-fees.data'))
        ->assertOk()
        ->assertJsonPath('data.0.education_level', 'SMA')
        ->assertJsonFragment(['amount' => 500000]);
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

    $this->getJson(route('registration-fees.data'))
        ->assertOk()
        ->assertJsonFragment(['fee_type_label' => 'Laboratorium Komputer']);
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


test('admin can configure promo discounts and up to five installments for a general fee', function () {
    $startsAt = now()->subDay()->format('Y-m-d H:i:s');
    $endsAt = now()->addDays(7)->format('Y-m-d H:i:s');

    $this->post(route('registration-fees.store'), [
        'fee_type' => 'Kegiatan',
        'name' => 'Study Tour',
        'billing_period' => 'once',
        'education_level' => '',
        'grade_level' => '',
        'amount' => 1000000,
        'is_active' => 1,
        'discount_enabled' => 1,
        'promo_code' => 'HEMAT20',
        'promo_starts_at' => $startsAt,
        'promo_ends_at' => $endsAt,
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_installments' => 5,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'fee_type_label' => 'Kegiatan',
        'name' => 'Study Tour',
        'discount_enabled' => true,
        'promo_code' => 'HEMAT20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_installments' => 5,
    ]);

    $this->getJson(route('registration-fees.data'))
        ->assertOk()
        ->assertJsonFragment(['promo_code' => 'HEMAT20', 'max_installments' => 5]);
});

test('registration fees cannot be configured with promo or installments', function () {
    $fee = RegistrationFee::where('education_level', 'SMA')
        ->where('grade_level', 10)
        ->firstOrFail();

    $this->put(route('registration-fees.update', $fee), [
        'fee_type' => 'Pendaftaran',
        'education_level' => 'SMA',
        'grade_level' => 10,
        'amount' => 400000,
        'is_active' => 1,
        'discount_enabled' => 1,
        'promo_code' => 'HEMAT50',
        'promo_starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
        'promo_ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'max_installments' => 5,
    ])->assertRedirect(route('registration-fees.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('registration_fees', [
        'id' => $fee->id,
        'discount_enabled' => false,
        'promo_code' => null,
        'promo_starts_at' => null,
        'promo_ends_at' => null,
        'discount_type' => null,
        'discount_value' => null,
        'max_installments' => 1,
    ]);
});

test('promo calculator applies fixed and percentage discounts only within the promo period', function () {
    $calculator = app(\App\Services\FeePaymentCalculator::class);
    $fee = RegistrationFee::make([
        'fee_type' => 'study-tour',
        'amount' => 100000,
        'discount_enabled' => true,
        'promo_code' => 'HEMAT20',
        'promo_starts_at' => now()->subDay(),
        'promo_ends_at' => now()->addDay(),
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_installments' => 5,
    ]);

    expect($calculator->calculate($fee, 'hemat20')['discount_amount'])->toBe(20000)
        ->and($calculator->calculate($fee, 'HEMAT20')['amount_due'])->toBe(80000);

    $fee->setAttribute('discount_type', 'fixed');
    $fee->setAttribute('discount_value', 15000);

    expect($calculator->calculate($fee, 'HEMAT20')['amount_due'])->toBe(85000);

    expect(fn () => $calculator->calculate($fee, 'WRONG'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => $calculator->calculate($fee, 'HEMAT20', now()->addDays(2)))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('installment calculator divides the remaining amount evenly and rejects more than the configured limit', function () {
    $calculator = app(\App\Services\FeePaymentCalculator::class);
    $fee = RegistrationFee::make([
        'fee_type' => 'study-tour',
        'amount' => 100,
        'max_installments' => 3,
    ]);

    expect($calculator->splitInstallments($fee, 100, 3))->toBe([34, 33, 33]);

    expect(fn () => $calculator->splitInstallments($fee, 100, 4))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    $registrationFee = RegistrationFee::make([
        'fee_type' => 'registration',
        'amount' => 200000,
        'max_installments' => 1,
    ]);

    expect(fn () => $calculator->splitInstallments($registrationFee, 200000, 2))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
