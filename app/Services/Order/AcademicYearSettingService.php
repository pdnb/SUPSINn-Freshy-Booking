<?php

namespace App\Services\Order;

use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class AcademicYearSettingService
{
    public const KEY = 'academic_year';

    public function year(): int
    {
        $value = Setting::query()->where('key', self::KEY)->value('value');

        if ($value === null || $value === '') {
            return now()->year + 543;
        }

        return (int) $value;
    }

    public function prefix(): string
    {
        return sprintf('%02d', $this->year() % 100);
    }

    public function update(string $year): void
    {
        $payload = Validator::make(
            ['year' => $year],
            ['year' => ['required', 'integer', 'digits:4', 'min:2500', 'max:2700']],
            [
                'year.required' => 'กรุณากรอกปีการศึกษา',
                'year.integer' => 'ปีการศึกษาต้องเป็นตัวเลข',
                'year.digits' => 'ปีการศึกษาต้องเป็น พ.ศ. 4 หลัก',
                'year.min' => 'ปีการศึกษาต้องอยู่ระหว่าง 2500–2700',
                'year.max' => 'ปีการศึกษาต้องอยู่ระหว่าง 2500–2700',
            ],
        )->validate();

        Setting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => (string) $payload['year']],
        );
    }
}
