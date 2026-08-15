<?php

namespace App\Enums;

enum SlipVerificationResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'ผ่าน',
            self::Fail => 'ไม่ผ่าน',
            self::Duplicate => 'สลิปซ้ำ',
        };
    }
}
