<?php

use App\Models\Product;
use App\Services\Booking\BookingRoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

function booking(): BookingRoundService
{
    return app(BookingRoundService::class);
}

test('it creates a round and attaches products', function () {
    $shirt = Product::factory()->create(['name' => 'เสื้อ ปี 69']);
    $combo = Product::factory()->bundle()->create(['name' => 'คอมโบ ปี 70']);

    $round = booking()->create([
        'name' => 'รอบเปิดจองปี 70',
        'starts_at' => '2026-08-01 09:00:00',
        'ends_at' => '2026-08-31 18:00:00',
        'is_enabled' => true,
        'product_ids' => [$shirt->id, $combo->id],
    ]);

    expect($round->products)->toHaveCount(2)
        ->and($round->products->pluck('id')->all())
        ->toEqualCanonicalizing([$shirt->id, $combo->id]);
});

test('a round outside its window is not open for the storefront', function () {
    $product = Product::factory()->create();

    $round = booking()->create([
        'name' => 'รอบสิงหาคม',
        'starts_at' => '2026-08-01 09:00:00',
        'ends_at' => '2026-08-31 18:00:00',
        'is_enabled' => true,
        'product_ids' => [$product->id],
    ]);

    Carbon::setTestNow('2026-07-31 23:59:00');
    expect(booking()->isOpen($round))->toBeFalse()
        ->and(booking()->storefrontProducts())->toHaveCount(0);

    Carbon::setTestNow('2026-08-15 12:00:00');
    expect(booking()->isOpen($round))->toBeTrue()
        ->and(booking()->storefrontProducts()->pluck('id')->all())->toBe([$product->id]);

    Carbon::setTestNow('2026-09-01 00:00:00');
    expect(booking()->isOpen($round))->toBeFalse()
        ->and(booking()->storefrontProducts())->toHaveCount(0);
});

test('a disabled round is not open even inside the window', function () {
    $product = Product::factory()->create();

    $round = booking()->create([
        'name' => 'รอบปิดมือ',
        'starts_at' => '2026-08-01 09:00:00',
        'ends_at' => '2026-08-31 18:00:00',
        'is_enabled' => false,
        'product_ids' => [$product->id],
    ]);

    Carbon::setTestNow('2026-08-15 12:00:00');

    expect(booking()->isOpen($round))->toBeFalse()
        ->and(booking()->storefrontProducts())->toHaveCount(0);
});

test('products not in an open round are excluded from the storefront query', function () {
    $onSale = Product::factory()->create(['name' => 'ในรอบ']);
    $notInRound = Product::factory()->create(['name' => 'นอกรอบ']);

    booking()->create([
        'name' => 'รอบเปิด',
        'starts_at' => '2026-08-01 09:00:00',
        'ends_at' => '2026-08-31 18:00:00',
        'is_enabled' => true,
        'product_ids' => [$onSale->id],
    ]);

    Carbon::setTestNow('2026-08-15 12:00:00');

    expect(booking()->storefrontProducts()->pluck('id')->all())->toBe([$onSale->id])
        ->and(booking()->isProductAvailable($notInRound))->toBeFalse()
        ->and(booking()->isProductAvailable($onSale))->toBeTrue();
});

test('an end time before the start time is rejected', function () {
    booking()->create([
        'name' => 'รอบกลับด้าน',
        'starts_at' => '2026-08-31 18:00:00',
        'ends_at' => '2026-08-01 09:00:00',
        'is_enabled' => true,
        'product_ids' => [],
    ]);
})->throws(ValidationException::class);
