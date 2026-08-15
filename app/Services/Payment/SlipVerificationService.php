<?php

namespace App\Services\Payment;

use App\Contracts\SlipVerifier;
use App\Enums\SlipVerificationResult;
use App\Models\PaymentSlip;
use Illuminate\Validation\ValidationException;

class SlipVerificationService
{
    public function __construct(private SlipVerifier $verifier) {}

    public function inspect(string $filePath, ?string $originalName = null): string
    {
        $result = $this->verifier->verify($filePath, $originalName);

        if ($result === SlipVerificationResult::Fail) {
            throw ValidationException::withMessages([
                'slip' => 'สลิปไม่ผ่านการตรวจ กรุณาแนบสลิปใหม่',
            ]);
        }

        if ($result === SlipVerificationResult::Duplicate) {
            throw ValidationException::withMessages([
                'slip' => 'สลิปนี้ถูกใช้แล้ว',
            ]);
        }

        $checksum = hash_file('sha256', $filePath);

        if ($checksum === false) {
            throw ValidationException::withMessages([
                'slip' => 'อ่านไฟล์สลิปไม่สำเร็จ',
            ]);
        }

        if (PaymentSlip::query()->where('checksum', $checksum)->exists()) {
            throw ValidationException::withMessages([
                'slip' => 'สลิปนี้ถูกใช้แล้ว',
            ]);
        }

        return $checksum;
    }
}
