<?php

use App\Models\User;

test('authenticated users can view their profile', function () {
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});
