<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated through the change password route', function () {
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/change-password')
        ->put('/change-password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    expect($user->must_change_password)->toBeFalse();
    expect($user->password_changed_at)->not->toBeNull();
});

test('current password is required to update the password', function () {
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/change-password')
        ->put('/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/change-password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
