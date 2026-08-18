<?php

use App\Services\Order\AcademicYearSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function academicYear(): AcademicYearSettingService
{
    return app(AcademicYearSettingService::class);
}

test('missing setting falls back to the current buddhist calendar year', function () {
    $this->travelTo('2026-08-18 10:00:00');

    expect(academicYear()->year())->toBe(2569)
        ->and(academicYear()->prefix())->toBe('69');
});

test('it stores a four-digit buddhist academic year', function () {
    academicYear()->update('2569');

    expect(academicYear()->year())->toBe(2569)
        ->and(academicYear()->prefix())->toBe('69');
});

test('changing the year updates the two-digit prefix', function () {
    academicYear()->update('2570');

    expect(academicYear()->year())->toBe(2570)
        ->and(academicYear()->prefix())->toBe('70');
});

test('update rejects a year that is not four buddhist-era digits', function (string $year) {
    academicYear()->update($year);
})->with(['', '69', '2026', '2499', '2701', 'abcd'])->throws(ValidationException::class);
