<?php

use App\Support\ThaiDate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('formats a bangkok datetime with a buddhist year and short thai month', function () {
    $at = Carbon::parse('2026-08-18 14:30:00', 'Asia/Bangkok');

    expect(ThaiDate::datetime($at))->toBe('18 ส.ค. 2569 14:30 น.');
});

test('omits a leading zero on the day', function () {
    $at = Carbon::parse('2026-08-08 09:05:00', 'Asia/Bangkok');

    expect(ThaiDate::datetime($at))->toBe('8 ส.ค. 2569 09:05 น.');
});

test('converts utc into the app timezone', function () {
    $at = Carbon::parse('2026-08-18 07:30:00', 'UTC');

    expect(ThaiDate::datetime($at))->toBe('18 ส.ค. 2569 14:30 น.');
});

test('returns an empty string for null', function () {
    expect(ThaiDate::datetime(null))->toBe('');
});

test('carbon macro formats through toThaiDatetime', function () {
    $at = Carbon::parse('2026-08-18 14:30:00', 'Asia/Bangkok');

    expect($at->toThaiDatetime())->toBe('18 ส.ค. 2569 14:30 น.');
});
