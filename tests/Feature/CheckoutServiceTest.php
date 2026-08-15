<?php

use App\Enums\FulfillmentMethod;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function checkout(): CheckoutService
{
    return app(CheckoutService::class);
}

function checkoutShirt(): Product
{
    return app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);
}

function fillCheckoutCart(): Product
{
    $shirt = checkoutShirt();
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);

    return $shirt;
}

function validBuyer(array $overrides = []): array
{
    return [
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
        'address_line' => null,
        'subdistrict' => null,
        'district' => null,
        'province' => null,
        'postcode' => null,
        ...$overrides,
    ];
}

function validPostAddress(array $overrides = []): array
{
    return [
        'address_line' => '123 ถนนสุขุมวิท',
        'subdistrict' => 'คลองเตย',
        'district' => 'คลองเตย',
        'province' => 'กรุงเทพฯ',
        'postcode' => '10110',
        ...$overrides,
    ];
}

test('pickup quote has zero shipping', function () {
    fillCheckoutCart();
    app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    $quote = checkout()->quote(FulfillmentMethod::Bookstore->value);

    expect($quote['subtotal'])->toBe('350.00')
        ->and($quote['shipping'])->toBe('0.00')
        ->and($quote['total'])->toBe('350.00');
});

test('postage quote uses the matching quantity tier from the first active rate', function () {
    $shirt = fillCheckoutCart();
    app(CartService::class)->add($shirt, ['options' => ['size' => 'L']], 2);

    app(ShippingRateService::class)->create([
        'name' => 'พื้นที่ห่างไกล',
        'amount' => '200',
        'is_active' => true,
        'sort_order' => 2,
    ]);
    $rate = app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'is_active' => true,
        'sort_order' => 1,
        'tiers' => [
            ['min_qty' => 1, 'max_qty' => 1, 'amount' => '50'],
            ['min_qty' => 2, 'max_qty' => 3, 'amount' => '80'],
            ['min_qty' => 4, 'max_qty' => null, 'amount' => '120'],
        ],
    ]);

    $quote = checkout()->quote(FulfillmentMethod::Post->value);

    expect($quote['shipping'])->toBe('80.00')
        ->and($quote['subtotal'])->toBe('1050.00')
        ->and($quote['total'])->toBe('1130.00')
        ->and($quote['shipping_rate_id'])->toBe($rate->id)
        ->and($quote['shipping_rate_name'])->toBe('ทั่วประเทศ');
});

test('postage quote includes the first active shipping rate', function () {
    fillCheckoutCart();
    app(ShippingRateService::class)->create([
        'name' => 'พื้นที่ห่างไกล',
        'amount' => '80',
        'is_active' => true,
        'sort_order' => 2,
    ]);
    $rate = app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $quote = checkout()->quote(FulfillmentMethod::Post->value);

    expect($quote['shipping'])->toBe('50.00')
        ->and($quote['total'])->toBe('400.00')
        ->and($quote['shipping_rate_id'])->toBe($rate->id)
        ->and($quote['shipping_rate_name'])->toBe('ทั่วประเทศ');
});

test('required buyer fields must be present to save checkout', function () {
    fillCheckoutCart();

    checkout()->save([
        'student_id' => '',
        'full_name' => '',
        'faculty' => '',
        'phone' => '',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);
})->throws(ValidationException::class);

test('postage requires a delivery address', function () {
    fillCheckoutCart();
    app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    checkout()->save(validBuyer([
        'fulfillment' => FulfillmentMethod::Post->value,
        ...validPostAddress(['address_line' => '']),
    ]));
})->throws(ValidationException::class);

test('postage requires a five-digit postcode', function () {
    fillCheckoutCart();
    app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    checkout()->save(validBuyer([
        'fulfillment' => FulfillmentMethod::Post->value,
        ...validPostAddress(['postcode' => '1011']),
    ]));
})->throws(ValidationException::class);

test('postage composes a full delivery address in the draft', function () {
    fillCheckoutCart();
    app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    $draft = checkout()->save(validBuyer([
        'fulfillment' => FulfillmentMethod::Post->value,
        ...validPostAddress(),
    ]));

    expect($draft['address'])->toBe('123 ถนนสุขุมวิท ตำบล/แขวง คลองเตย อำเภอ/เขต คลองเตย จังหวัด กรุงเทพฯ 10110')
        ->and($draft['address_line'])->toBe('123 ถนนสุขุมวิท')
        ->and($draft['postcode'])->toBe('10110');
});

test('checkout is rejected when the booking round is no longer open', function () {
    $shirt = fillCheckoutCart();
    $shirt->bookingRounds->each->update([
        'ends_at' => now()->subMinute(),
    ]);

    checkout()->save(validBuyer());
})->throws(ValidationException::class);
