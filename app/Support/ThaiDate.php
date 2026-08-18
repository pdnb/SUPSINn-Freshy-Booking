<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class ThaiDate
{
    /**
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'ม.ค.',
        2 => 'ก.พ.',
        3 => 'มี.ค.',
        4 => 'เม.ย.',
        5 => 'พ.ค.',
        6 => 'มิ.ย.',
        7 => 'ก.ค.',
        8 => 'ส.ค.',
        9 => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.',
    ];

    public static function datetime(?CarbonInterface $at): string
    {
        if ($at === null) {
            return '';
        }

        $local = $at->copy()->timezone(config('app.timezone'));

        return sprintf(
            '%d %s %d %s น.',
            $local->day,
            self::MONTHS[$local->month],
            $local->year + 543,
            $local->format('H:i'),
        );
    }
}
