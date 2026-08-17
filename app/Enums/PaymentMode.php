<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Full = 'full';
    case Deposit = 'deposit';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'ชำระเต็ม',
            self::Deposit => 'ชำระมัดจำ',
        };
    }
}
