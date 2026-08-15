<?php

namespace App\Contracts;

use App\Enums\SlipVerificationResult;

interface SlipVerifier
{
    public function verify(string $filePath, ?string $originalName = null): SlipVerificationResult;
}
