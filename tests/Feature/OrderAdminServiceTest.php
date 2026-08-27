<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusChange;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function adminOrders(): OrderService
{
    return app(OrderService::class);
}

test('the queue can be filtered by created date range', function () {
    Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRDATE001',
        'created_at' => now()->timezone(config('app.timezone'))->subDays(2)->setTime(10, 0),
    ]);
    Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRDATE002',
        'created_at' => now()->timezone(config('app.timezone'))->setTime(11, 0),
    ]);

    $today = now()->timezone(config('app.timezone'))->toDateString();

    expect(adminOrders()->queue([
        'date_from' => $today,
        'date_to' => $today,
    ])->pluck('number')->all())->toBe(['FRDATE002']);
});

test('the default queue lists only pending review orders', function () {
    Order::factory()->create(['status' => OrderStatus::PendingReview, 'number' => 'FRQUEUE01']);
    Order::factory()->create(['status' => OrderStatus::Confirmed, 'number' => 'FRDONE001']);

    $queue = adminOrders()->queue();

    expect($queue)->toHaveCount(1)
        ->and($queue->first()->number)->toBe('FRQUEUE01');
});

test('the queue can be searched by order number or student id', function () {
    Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRSEARCH1',
        'student_id' => '67011111111',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRSEARCH2',
        'student_id' => '67012222222',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FROTHER01',
        'student_id' => '67013333333',
    ]);

    expect(adminOrders()->queue(['search' => 'FRSEARCH1', 'status' => null])->pluck('number')->all())
        ->toBe(['FRSEARCH1'])
        ->and(adminOrders()->queue(['search' => '67012222222', 'status' => null])->pluck('number')->all())
        ->toBe(['FRSEARCH2']);
});

test('the queue can be searched by name or phone', function () {
    Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRNAME001',
        'full_name' => 'สมชาย ใจดี',
        'phone' => '0891111111',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRNAME002',
        'full_name' => 'สมหญิง ใจงาม',
        'phone' => '0892222222',
    ]);

    expect(adminOrders()->queue(['search' => 'สมชาย', 'status' => null])->pluck('number')->all())
        ->toBe(['FRNAME001'])
        ->and(adminOrders()->queue(['search' => '0892222222', 'status' => null])->pluck('number')->all())
        ->toBe(['FRNAME002']);
});

test('staff can confirm or reject a pending review order', function () {
    $staff = User::factory()->create();
    $confirm = Order::factory()->create(['status' => OrderStatus::PendingReview]);
    $reject = Order::factory()->create(['status' => OrderStatus::PendingReview]);

    adminOrders()->transition($confirm, OrderStatus::Confirmed, $staff);
    adminOrders()->transition($reject, OrderStatus::NeedReslip, $staff);

    expect($confirm->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($reject->fresh()->status)->toBe(OrderStatus::NeedReslip);
});

test('staff cannot skip from pending review to ready for pickup', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PendingReview]);

    expect(fn () => adminOrders()->transition($order, OrderStatus::ReadyForPickup, User::factory()->create()))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::PendingReview);
});

test('confirmed pickup orders move to ready for pickup not shipped', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Bookstore,
    ]);

    expect(fn () => adminOrders()->transition($order, OrderStatus::Shipped, $staff))
        ->toThrow(ValidationException::class);

    adminOrders()->transition($order, OrderStatus::ReadyForPickup, $staff);

    expect($order->fresh()->status)->toBe(OrderStatus::ReadyForPickup);
});

test('pickup ready for pickup can revert to confirmed', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'fulfillment' => FulfillmentMethod::Bookstore,
    ]);

    adminOrders()->transition($order, OrderStatus::Confirmed, $staff);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

test('confirmed postal orders move to shipped not ready for pickup', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '123 ถนนทดสอบ',
    ]);

    expect(fn () => adminOrders()->transition($order, OrderStatus::ReadyForPickup, $staff))
        ->toThrow(ValidationException::class);

    adminOrders()->transition($order, OrderStatus::Shipped, $staff);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('staff can mark postal orders shipped with or without a parcel number', function () {
    $staff = User::factory()->create();
    $withNumber = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '123 ถนนทดสอบ',
        'number' => 'FRPARCEL1',
    ]);
    $withoutNumber = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '456 ถนนทดสอบ',
        'number' => 'FRPARCEL2',
    ]);

    $shippedWith = adminOrders()->markShipped($withNumber, $staff, '  EMS123456TH  ');
    $shippedWithout = adminOrders()->markShipped($withoutNumber, $staff, '   ');

    expect($shippedWith->status)->toBe(OrderStatus::Shipped)
        ->and($shippedWith->parcel_number)->toBe('EMS123456TH')
        ->and($shippedWithout->status)->toBe(OrderStatus::Shipped)
        ->and($shippedWithout->parcel_number)->toBeNull();
});

test('staff cannot save a parcel number on a bookstore order', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Bookstore,
    ]);

    expect(fn () => adminOrders()->markShipped($order, $staff, 'EMS123'))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->fresh()->parcel_number)->toBeNull();
});

test('staff can update or clear a parcel number after shipping', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '123 ถนนทดสอบ',
        'number' => 'FRPARCEL3',
    ]);

    $shipped = adminOrders()->markShipped($order, $staff, 'EMS111');
    $updated = adminOrders()->updateParcelNumber($shipped, $staff, 'EMS222');

    expect($updated->parcel_number)->toBe('EMS222');

    $cleared = adminOrders()->updateParcelNumber($updated, $staff, '');

    expect($cleared->status)->toBe(OrderStatus::Shipped)
        ->and($cleared->parcel_number)->toBeNull();
});

test('the post awaiting parcel queue lists confirmed and shipped without a number', function () {
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'number' => 'FRWAIT001',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => null,
        'number' => 'FRWAIT002',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => 'EMS999',
        'number' => 'FRDONE001',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Bookstore,
        'number' => 'FRBOOK001',
    ]);

    expect(adminOrders()->queue([
        'fulfillment' => FulfillmentMethod::Post,
        'awaiting_parcel' => true,
    ])->pluck('number')->all())->toEqualCanonicalizing(['FRWAIT001', 'FRWAIT002']);
});

test('marking pickup issues a receipt and completes the order', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create(['status' => OrderStatus::ReadyForPickup]);

    $updated = adminOrders()->markPickedUp($order, $staff);

    expect($updated->status)->toBe(OrderStatus::Completed)
        ->and($updated->receipt_issued_at)->not->toBeNull();
});

test('deposit orders cannot be completed until the remaining balance is collected', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->deposit()->create(['status' => OrderStatus::ReadyForPickup]);

    expect($order->hasOutstandingBalance())->toBeTrue()
        ->and(fn () => adminOrders()->markPickedUp($order, $staff))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::ReadyForPickup);

    adminOrders()->collectBalance($order, $staff);
    $updated = adminOrders()->markPickedUp($order->fresh(), $staff);

    expect($updated->status)->toBe(OrderStatus::Completed)
        ->and($updated->balance_collected_at)->not->toBeNull();
});

test('a receipt cannot be issued before the order is ready to fulfill', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PendingReview]);

    expect(fn () => adminOrders()->issueReceipt($order, User::factory()->create()))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->receipt_issued_at)->toBeNull();
});

test('status changes are audited with the acting staff member', function () {
    $staff = User::factory()->create(['name' => 'เจ้าหน้าที่ทดสอบ']);
    $order = Order::factory()->create(['status' => OrderStatus::PendingReview]);

    adminOrders()->transition($order, OrderStatus::Confirmed, $staff);

    $change = OrderStatusChange::query()->first();

    expect($change)->not->toBeNull()
        ->and($change->order_id)->toBe($order->id)
        ->and($change->from_status)->toBe(OrderStatus::PendingReview)
        ->and($change->to_status)->toBe(OrderStatus::Confirmed)
        ->and($change->user_id)->toBe($staff->id);
});
