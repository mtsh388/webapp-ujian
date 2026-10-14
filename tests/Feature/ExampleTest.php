<?php

it('redirects the root route to the dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('dashboard', absolute: false));
});
