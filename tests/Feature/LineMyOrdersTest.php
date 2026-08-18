<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Line\LineIdentityService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('orders bound to the current line user appear on the orders page', function () {
    session(['line.user_id' => 'Ulineuser00000000000000000000001']);

    $mineA = Order::factory()->create([
        'number' => 'FRMINE0001',
        'line_user_id' => 'Ulineuser00000000000000000000001',
        'status' => OrderStatus::PendingReview,
        'total' => '350.00',
    ]);
    $mineB = Order::factory()->create([
        'number' => 'FRMINE0002',
        'line_user_id' => 'Ulineuser00000000000000000000001',
        'status' => OrderStatus::Confirmed,
        'total' => '700.00',
    ]);
    Order::factory()->create([
        'number' => 'FROTHER01',
        'line_user_id' => 'Usomeoneelse0000000000000000002',
        'full_name' => 'ไม่ควรเห็นชื่อนี้',
    ]);
    Order::factory()->create([
        'number' => 'FRNONE001',
        'line_user_id' => null,
        'full_name' => 'ออเดอร์เว็บธรรมดา',
    ]);

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ออเดอร์ของฉัน', false)
        ->assertSee('FRMINE0001', false)
        ->assertSee('FRMINE0002', false)
        ->assertSee('รอเจ้าหน้าที่ตรวจสลิป', false)
        ->assertSee('ยืนยันการชำระแล้ว', false)
        ->assertDontSee('FROTHER01', false)
        ->assertDontSee('ไม่ควรเห็นชื่อนี้', false)
        ->assertDontSee('FRNONE001', false)
        ->assertDontSee('ออเดอร์เว็บธรรมดา', false)
        ->assertSee(route('orders.confirmation', [
            'order' => $mineA,
            'token' => $mineA->tracking_token,
        ]), false)
        ->assertSee(route('orders.confirmation', [
            'order' => $mineB,
            'token' => $mineB->tracking_token,
        ]), false);
});

test('a line identity with no orders shows the empty state without guest redirect', function () {
    session(['line.user_id' => 'Ulineuser00000000000000000000099']);

    $tracked = Order::factory()->create([
        'number' => 'FRGUEST01',
        'tracking_token' => str_repeat('g', 40),
    ]);
    app(OrderService::class)->rememberGuestTracking($tracked);

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ยังไม่มีคำสั่งซื้อ', false)
        ->assertSee('เมื่อจองผ่าน LINE และแนบสลิปแล้ว', false)
        ->assertDontSee('FRGUEST01', false);
});

test('placing an order copies the line user id from the session', function () {
    Storage::fake('local');
    session(['line.user_id' => 'Ulineuser00000000000000000000042']);

    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);

    $order = app(OrderService::class)->place(UploadedFile::fake()->image('slip.jpg'));

    expect($order->line_user_id)->toBe('Ulineuser00000000000000000000042');
});

test('placing an order without a line session leaves line_user_id null', function () {
    Storage::fake('local');

    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);

    $order = app(OrderService::class)->place(UploadedFile::fake()->image('slip.jpg'));

    expect($order->line_user_id)->toBeNull();
});

test('ordersForCurrentUser returns only matching line orders', function () {
    session(['line.user_id' => 'Ulineuser00000000000000000000007']);

    Order::factory()->create([
        'number' => 'FRMATCH01',
        'line_user_id' => 'Ulineuser00000000000000000000007',
    ]);
    Order::factory()->create([
        'number' => 'FRSKIP001',
        'line_user_id' => 'Uother',
    ]);

    $orders = app(LineIdentityService::class)->ordersForCurrentUser();

    expect($orders)->toHaveCount(1)
        ->and($orders->first()->number)->toBe('FRMATCH01');
});

test('livewire order-track lists line orders when identity is present', function () {
    session(['line.user_id' => 'Ulineuser00000000000000000000011']);

    $order = Order::factory()->create([
        'number' => 'FRLW00001',
        'line_user_id' => 'Ulineuser00000000000000000000011',
        'status' => OrderStatus::Shipped,
        'created_at' => now()->timezone(config('app.timezone'))->setTime(14, 30),
    ]);

    Livewire::test('pages::storefront.order-track')
        ->assertOk()
        ->assertSee('FRLW00001')
        ->assertSee('จัดส่งแล้ว')
        ->assertSee($order->created_at->toThaiDatetime());
});
