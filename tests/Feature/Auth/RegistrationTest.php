<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('registration page is available to guests', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Buat Akun Siswa');
});

test('a visitor can register a student account', function () {
    $this->post('/register', [
        'email' => 'siswa@example.com',
        'password' => 'password123',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $user = User::where('email', 'siswa@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('siswa@example.com')
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->hasRole('Siswa'))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'email' => 'siswa@example.com',
    ]);
});

test('registration requires a valid unique email and password', function () {
    User::factory()->create([
        'email' => 'siswa@example.com',
    ]);

    $this->from('/register')
        ->post('/register', [
            'email' => 'siswa@example.com',
            'password' => 'short',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors(['email', 'password']);
});
