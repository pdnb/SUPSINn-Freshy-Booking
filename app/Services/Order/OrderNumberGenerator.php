<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderNumberGenerator
{
    public const PREFIX = 'SRU';

    public function __construct(private AcademicYearSettingService $academicYear) {}

    public function next(): string
    {
        return DB::transaction(function (): string {
            $yearPrefix = $this->academicYear->prefix();
            $pattern = self::PREFIX.$yearPrefix.'-____';

            $latest = Order::query()
                ->where('number', 'like', $pattern)
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $sequence = $latest === null ? 1 : ((int) Str::afterLast($latest, '-')) + 1;

            if ($sequence > 9999) {
                throw ValidationException::withMessages([
                    'number' => 'รหัสคำสั่งซื้อของปีการศึกษานี้เต็มแล้ว กรุณาเปลี่ยนปีการศึกษา',
                ]);
            }

            return sprintf('%s%s-%04d', self::PREFIX, $yearPrefix, $sequence);
        });
    }
}
