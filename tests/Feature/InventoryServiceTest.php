<?php

use App\Enums\FulfillmentMethod;
use App\Enums\InventoryAdjustmentReason;
use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function inventory(): InventoryService
{
    return app(InventoryService::class);
}

function inventoryShirt(): Product
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

test('inventory lists catalog choices with zero on hand by default', function () {
    inventoryShirt();

    $rows = inventory()->list();

    expect($rows)->toHaveCount(3)
        ->and($rows[0]['choice_value'])->toBe('L')
        ->and($rows[0]['on_hand'])->toBe(0)
        ->and($rows[0]['confirmed_qty'])->toBe(0)
        ->and($rows[0]['is_low'])->toBeFalse();
});

test('inventory compares on-hand stock to confirmed order quantities', function () {
    $shirt = inventoryShirt();
    InventoryItem::factory()->create([
        'product_id' => $shirt->id,
        'choice_label' => 'ไซส์เสื้อ',
        'choice_value' => 'M',
        'on_hand' => 4,
        'threshold' => 2,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);
    $order->items()->create([
        'product_id' => $shirt->id,
        'name' => $shirt->name,
        'price' => $shirt->price,
        'qty' => 3,
        'choices' => [['label' => 'ไซส์เสื้อ', 'value' => 'M']],
    ]);

    $row = collect(inventory()->list())->first(
        fn (array $row): bool => $row['choice_value'] === 'M',
    );

    expect($row['on_hand'])->toBe(4)
        ->and($row['confirmed_qty'])->toBe(3)
        ->and($row['remaining'])->toBe(1)
        ->and($row['is_low'])->toBeTrue();
});

test('staff can adjust on-hand inventory without blocking later bookings', function () {
    $shirt = inventoryShirt();
    $staff = User::factory()->create();

    $item = inventory()->adjust(
        $shirt,
        'ไซส์เสื้อ',
        'M',
        12,
        InventoryAdjustmentReason::FactoryReceipt,
        $staff,
    );

    expect($item->on_hand)->toBe(12)
        ->and($item->adjustments)->toHaveCount(1)
        ->and($item->adjustments->first()->reason)->toBe(InventoryAdjustmentReason::FactoryReceipt);
});

test('inventory cannot be adjusted below zero', function () {
    $shirt = inventoryShirt();

    expect(fn () => inventory()->adjust(
        $shirt,
        'ไซส์เสื้อ',
        'M',
        -1,
        InventoryAdjustmentReason::Damaged,
        User::factory()->create(),
    ))->toThrow(ValidationException::class);
});

test('placing an order does not reduce on-hand inventory', function () {
    Storage::fake('local');
    $shirt = inventoryShirt();
    openBookingRound([$shirt]);
    inventory()->adjust(
        $shirt,
        'ไซส์เสื้อ',
        'M',
        8,
        InventoryAdjustmentReason::FactoryReceipt,
        User::factory()->create(),
    );

    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);

    app(OrderService::class)->place(UploadedFile::fake()->image('slip.jpg'));

    expect(InventoryItem::query()->where('choice_value', 'M')->value('on_hand'))->toBe(8);
});
