<?php

use App\Models\Order;
use App\Services\Order\AcademicYearSettingService;
use App\Services\Order\OrderNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function orderNumbers(): OrderNumberGenerator
{
    return app(OrderNumberGenerator::class);
}

test('the first number of an academic year is FB-YY-0001', function () {
    app(AcademicYearSettingService::class)->update('2569');

    expect(orderNumbers()->next())->toBe('FB-69-0001');
});

test('the running number increments within the same academic year', function () {
    app(AcademicYearSettingService::class)->update('2569');
    Order::factory()->create(['number' => 'FB-69-0001']);

    expect(orderNumbers()->next())->toBe('FB-69-0002');
});

test('legacy eight-digit numbers are ignored when counting the sequence', function () {
    app(AcademicYearSettingService::class)->update('2569');
    Order::factory()->create(['number' => '69000001']);
    Order::factory()->create(['number' => 'FB-69-0003']);

    expect(orderNumbers()->next())->toBe('FB-69-0004');
});

test('a new academic year restarts the running number', function () {
    app(AcademicYearSettingService::class)->update('2570');
    Order::factory()->create(['number' => 'FB-69-0042']);

    expect(orderNumbers()->next())->toBe('FB-70-0001');
});

test('it rejects a new number when the year sequence is full', function () {
    app(AcademicYearSettingService::class)->update('2569');
    Order::factory()->create(['number' => 'FB-69-9999']);

    expect(fn () => orderNumbers()->next())->toThrow(ValidationException::class);
});
