<?php

namespace App\Services\Payment;

use App\Contracts\SlipVerifier;
use App\Enums\SlipVerificationResult;

final class StubSlipVerifier implements SlipVerifier
{
    public function verify(string $filePath, ?string $originalName = null): SlipVerificationResult
    {
        $haystack = strtolower($originalName ?? basename($filePath));

        if (str_contains($haystack, 'fail')) {
            return SlipVerificationResult::Fail;
        }

        if (str_contains($haystack, 'dup')) {
            return SlipVerificationResult::Duplicate;
        }

        return SlipVerificationResult::Pass;
    }
}
