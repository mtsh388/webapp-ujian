<?php

test('email verification endpoints are not exposed by the custom authentication flow', function () {
    $this->get('/verify-email')->assertNotFound();
});
