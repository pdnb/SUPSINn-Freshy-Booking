<?php

use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function shipping(): ShippingRateService
{
    return app(ShippingRateService::class);
}

test('it creates a fixed shipping rate', function () {
    $rate = shipping()->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50.00',
        'is_active' => true,
    ]);

    expect($rate->name)->toBe('ทั่วประเทศ')
        ->and($rate->amount)->toBe('50.00')
        ->and($rate->is_active)->toBeTrue();
});

test('it updates a shipping rate', function () {
    $rate = shipping()->create([
        'name' => 'กรุงเทพฯ',
        'amount' => '30.00',
        'is_active' => true,
    ]);

    $updated = shipping()->update($rate, [
        'name' => 'กรุงเทพฯ และปริมณฑล',
        'amount' => '35.00',
        'is_active' => true,
    ]);

    expect($updated->name)->toBe('กรุงเทพฯ และปริมณฑล')
        ->and($updated->amount)->toBe('35.00');
});

test('active rates exclude inactive ones for the storefront', function () {
    shipping()->create(['name' => 'ทั่วประเทศ', 'amount' => '50', 'is_active' => true]);
    $inactive = shipping()->create(['name' => 'เรทเก่า', 'amount' => '20', 'is_active' => true]);
    shipping()->setActive($inactive, false);

    $active = shipping()->active();

    expect($active)->toHaveCount(1)
        ->and($active->first()->name)->toBe('ทั่วประเทศ');
});

test('a negative amount is rejected', function () {
    shipping()->create([
        'name' => 'ผิด',
        'amount' => '-10',
    ]);
})->throws(ValidationException::class);

test('creating with only an amount stores an implicit open-ended tier', function () {
    $rate = shipping()->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    expect($rate->tiers)->toBe([
        [
            'min_qty' => 1,
            'max_qty' => null,
            'amount' => '50.00',
        ],
    ])->and($rate->amount)->toBe('50.00');
});

test('creating with tiers syncs amount from the first tier', function () {
    $rate = shipping()->create([
        'name' => 'ทั่วประเทศ',
        'tiers' => [
            ['min_qty' => 1, 'max_qty' => 1, 'amount' => '50'],
            ['min_qty' => 2, 'max_qty' => 3, 'amount' => '80'],
            ['min_qty' => 4, 'max_qty' => null, 'amount' => '120'],
        ],
    ]);

    expect($rate->amount)->toBe('50.00')
        ->and($rate->tiers)->toHaveCount(3)
        ->and($rate->tiers[2])->toMatchArray([
            'min_qty' => 4,
            'max_qty' => null,
            'amount' => '120.00',
        ]);
});

test('amountForQty matches the last tier whose min is at most the cart quantity', function (int $qty, string $expected) {
    $rate = shipping()->create([
        'name' => 'ทั่วประเทศ',
        'tiers' => [
            ['min_qty' => 1, 'max_qty' => 1, 'amount' => '50'],
            ['min_qty' => 2, 'max_qty' => 3, 'amount' => '80'],
            ['min_qty' => 4, 'max_qty' => null, 'amount' => '120'],
        ],
    ]);

    expect(shipping()->amountForQty($rate, $qty))->toBe($expected);
})->with([
    'single item' => [1, '50.00'],
    'middle of a range' => [3, '80.00'],
    'start of the last range' => [4, '120.00'],
    'overflow uses the last tier' => [9, '120.00'],
]);

test('amountForQty uses the previous tier when a gap is left in the middle', function () {
    $rate = shipping()->create([
        'name' => 'ทั่วประเทศ',
        'tiers' => [
            ['min_qty' => 1, 'max_qty' => 1, 'amount' => '50'],
            ['min_qty' => 4, 'max_qty' => null, 'amount' => '120'],
        ],
    ]);

    expect(shipping()->amountForQty($rate, 2))->toBe('50.00')
        ->and(shipping()->amountForQty($rate, 3))->toBe('50.00')
        ->and(shipping()->amountForQty($rate, 4))->toBe('120.00');
});

test('duplicate min_qty values are rejected', function () {
    shipping()->create([
        'name' => 'ทั่วประเทศ',
        'tiers' => [
            ['min_qty' => 1, 'max_qty' => 1, 'amount' => '50'],
            ['min_qty' => 1, 'max_qty' => 3, 'amount' => '80'],
        ],
    ]);
})->throws(ValidationException::class);

test('a min_qty below one is rejected', function () {
    shipping()->create([
        'name' => 'ทั่วประเทศ',
        'tiers' => [
            ['min_qty' => 0, 'max_qty' => 1, 'amount' => '50'],
        ],
    ]);
})->throws(ValidationException::class);
