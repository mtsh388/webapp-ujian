<?php

test('unsupported password confirmation endpoint is not exposed', function () {
    $this->get('/confirm-password')->assertNotFound();
});
