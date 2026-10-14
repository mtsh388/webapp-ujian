<?php

use App\Support\QuestionHtml;

test('question HTML sanitizer removes scripts and event handlers', function () {
    $html = QuestionHtml::sanitize('<p onclick="alert(1)">Safe<script>alert(2)</script></p>');

    expect($html)
        ->toContain('Safe')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('alert(1)')
        ->not->toContain('alert(2)');
});

test('question HTML sanitizer removes dangerous link protocols', function () {
    $html = QuestionHtml::sanitize('<a href="javascript:alert(1)">Open</a><a href="https://example.com">Safe link</a>');

    expect($html)
        ->toContain('Open')
        ->toContain('Safe link')
        ->toContain('https://example.com')
        ->not->toContain('javascript:');
});
