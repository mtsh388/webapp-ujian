<?php

it('shows the version selector on the root route', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Pilih versi aplikasi')
        ->assertSee('/v1')
        ->assertSee('/v2');
});

it('redirects the version two root to its login page', function () {
    $response = $this->get('/v2');

    $response->assertRedirect('/v2/login');
});
