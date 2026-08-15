<?php

use App\Enums\SlipVerificationResult;
use App\Services\Payment\StubSlipVerifier;

test('the stub slip verifier passes by default', function () {
    expect((new StubSlipVerifier)->verify('unused-path', 'slip.jpg'))
        ->toBe(SlipVerificationResult::Pass);
});

test('the stub slip verifier fails when the filename contains fail', function () {
    expect((new StubSlipVerifier)->verify('unused-path', 'fail-slip.jpg'))
        ->toBe(SlipVerificationResult::Fail);
});

test('the stub slip verifier flags duplicates when the filename contains dup', function () {
    expect((new StubSlipVerifier)->verify('unused-path', 'dup-slip.jpg'))
        ->toBe(SlipVerificationResult::Duplicate);
});
