<?php

namespace App\Services\Checkout;

use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class DepositSettingService
{
    public const KEY = 'deposit_amount';

    public function amount(): string
    {
        $value = Setting::query()->where('key', self::KEY)->value('value');

        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    public function update(string $amount): void
    {
        $payload = Validator::make(
            ['amount' => $amount],
            ['amount' => ['required', 'numeric', 'min:0']],
            [
                'amount.required' => 'กรุณากรอกจำนวนมัดจำ',
                'amount.numeric' => 'จำนวนมัดจำต้องเป็นตัวเลข',
                'amount.min' => 'จำนวนมัดจำต้องไม่ติดลบ',
            ],
        )->validate();

        Setting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => number_format((float) $payload['amount'], 2, '.', '')],
        );
    }
}
