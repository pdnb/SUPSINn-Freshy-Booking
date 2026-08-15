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

test('marking pickup issues a receipt and completes the order', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create(['status' => OrderStatus::ReadyForPickup]);

    $updated = adminOrders()->markPickedUp($order, $staff);

    expect($updated->status)->toBe(OrderStatus::Completed)
        ->and($updated->receipt_issued_at)->not->toBeNull();
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
