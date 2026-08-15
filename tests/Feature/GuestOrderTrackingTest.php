<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentSlip;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('a valid tracking link shows the guest order status', function () {
    $order = Order::factory()->create([
        'number' => 'FRTRACK01',
        'tracking_token' => str_repeat('a', 40),
        'status' => OrderStatus::PendingReview,
        'full_name' => 'สมชาย ติดตาม',
        'student_id' => '67019999999',
    ]);

    $order->items()->create([
        'product_id' => null,
        'name' => 'เสื้อ ปี 69',
        'price' => '350.00',
        'qty' => 1,
        'choices' => [
            ['label' => 'ไซส์เสื้อ', 'value' => 'M'],
            ['label' => 'ไซส์กางเกง', 'value' => 'L'],
        ],
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('เลขที่จอง', false)
        ->assertSee('คัดลอกเลขที่จอง FRTRACK01', false)
        ->assertSee('FRTRACK01', false)
        ->assertSee('รอเจ้าหน้าที่ตรวจสลิป', false)
        ->assertSee('สมชาย ติดตาม', false)
        ->assertSee('67019999999', false)
        ->assertSee('เสื้อ ปี 69', false)
        ->assertSee('× 1', false)
        ->assertSee('ไซส์เสื้อ · M', false)
        ->assertSee('ไซส์กางเกง · L', false)
        ->assertDontSee('ไซส์เสื้อ · M · ไซส์กางเกง · L', false)
        ->assertSee('ยอดสินค้า', false)
        ->assertSee('ค่าส่ง', false)
        ->assertSee('350.00 บาท', false)
        ->assertSee('0.00 บาท', false)
        ->assertSee('mt-1 text-right text-muted', false)
        ->assertSee('mt-4 space-y-2 rounded-brand border border-border bg-surface p-4 text-sm', false);

    Livewire::test('pages::storefront.order-confirmation', [
        'order' => $order->number,
        'token' => $order->tracking_token,
    ])
        ->assertOk()
        ->assertSee('FRTRACK01')
        ->assertSee('รอเจ้าหน้าที่ตรวจสลิป');
});

test('an invalid tracking token does not reveal order details', function () {
    $order = Order::factory()->create([
        'number' => 'FRSECRET1',
        'tracking_token' => str_repeat('b', 40),
        'full_name' => 'ห้ามโชว์ชื่อนี้',
        'student_id' => '67018888888',
        'status' => OrderStatus::Confirmed,
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => str_repeat('c', 40),
    ]))
        ->assertNotFound()
        ->assertDontSee('ห้ามโชว์ชื่อนี้', false)
        ->assertDontSee('67018888888', false)
        ->assertDontSee('FRSECRET1', false);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => 'short',
    ]))
        ->assertNotFound()
        ->assertDontSee('ห้ามโชว์ชื่อนี้', false);
});

test('a missing tracking token is rejected', function () {
    $order = Order::factory()->create([
        'number' => 'FRMISS001',
        'tracking_token' => str_repeat('d', 40),
        'full_name' => 'ไม่มีโทเคน',
    ]);

    $this->get('/orders/'.$order->number.'/')
        ->assertNotFound();
});

test('order service resolves guest tracking only with a matching token', function () {
    $order = Order::factory()->create([
        'number' => 'FRSVC0001',
        'tracking_token' => str_repeat('e', 40),
        'full_name' => 'ผ่านบริการ',
    ]);

    $orders = app(OrderService::class);

    expect($orders->findForGuestTracking('FRSVC0001', str_repeat('e', 40)))
        ->not->toBeNull()
        ->number->toBe('FRSVC0001')
        ->and($orders->findForGuestTracking('FRSVC0001', str_repeat('f', 40)))->toBeNull()
        ->and($orders->findForGuestTracking('UNKNOWN1', str_repeat('e', 40)))->toBeNull();
});

test('the orders page shows an empty state when the guest has no tracking link', function () {
    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ยังไม่มีคำสั่งซื้อ', false)
        ->assertSee('ไปหน้าหลัก', false)
        ->assertDontSee('โทเคนติดตาม', false);
});

test('remembered guest tracking reopens the order from the orders tab', function () {
    $order = Order::factory()->create([
        'number' => 'FRSESSION',
        'tracking_token' => str_repeat('j', 40),
    ]);

    app(OrderService::class)->rememberGuestTracking($order);

    $this->get(route('orders.index'))
        ->assertRedirect(route('orders.confirmation', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]));
});

test('opening a valid tracking link remembers it for the orders tab', function () {
    $order = Order::factory()->create([
        'number' => 'FRREOPEN1',
        'tracking_token' => str_repeat('k', 40),
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))->assertOk();

    expect(session('order.tracking'))->toMatchArray([
        'number' => 'FRREOPEN1',
        'token' => str_repeat('k', 40),
    ]);
});

test('a tracking page is not indexable and does not embed the token in html', function () {
    $order = Order::factory()->create([
        'number' => 'FRNOINDEX',
        'tracking_token' => str_repeat('n', 40),
        'full_name' => 'ไม่ให้ค้นหา',
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee('ไม่ให้ค้นหา', false)
        ->assertDontSee('tracking_token', false);
});

test('an unknown order number is a 404 without probing a model first', function () {
    $this->get(route('orders.confirmation', [
        'order' => 'FRMISSING',
        'token' => str_repeat('z', 40),
    ]))
        ->assertNotFound()
        ->assertDontSee('FRMISSING', false);
});

test('the tracking page makes the slip filename previewable', function () {
    $order = Order::factory()->create([
        'number' => 'FRSLIP001',
        'tracking_token' => str_repeat('p', 40),
    ]);

    PaymentSlip::factory()->create([
        'order_id' => $order->id,
        'original_name' => 'sru-creditbank-onboarding-square.png',
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('sru-creditbank-onboarding-square.png', false)
        ->assertSee('ดูสลิป sru-creditbank-onboarding-square.png', false)
        ->assertSee('/orders/FRSLIP001/slip', false);
});

test('a guest with a tracking session can preview their slip', function () {
    Storage::fake('local');
    Storage::disk('local')->put('slips/1/slip.png', 'fake-slip-bytes');

    $order = Order::factory()->create([
        'number' => 'FRSLIP001',
        'tracking_token' => str_repeat('p', 40),
    ]);

    PaymentSlip::factory()->create([
        'order_id' => $order->id,
        'path' => 'slips/1/slip.png',
        'original_name' => 'sru-creditbank-onboarding-square.png',
    ]);

    app(OrderService::class)->rememberGuestTracking($order);

    $this->get(route('orders.slip', $order))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'inline; filename="sru-creditbank-onboarding-square.png"')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('a guest cannot preview another order slip or one without a tracking session', function () {
    Storage::fake('local');
    Storage::disk('local')->put('slips/1/slip.png', 'fake-slip-bytes');

    $order = Order::factory()->create([
        'number' => 'FRSLIP002',
        'tracking_token' => str_repeat('q', 40),
    ]);

    PaymentSlip::factory()->create([
        'order_id' => $order->id,
        'path' => 'slips/1/slip.png',
        'original_name' => 'private-slip.png',
    ]);

    $this->get(route('orders.slip', $order))
        ->assertNotFound()
        ->assertDontSee('private-slip.png', false);

    $other = Order::factory()->create([
        'tracking_token' => str_repeat('r', 40),
    ]);

    app(OrderService::class)->rememberGuestTracking($other);

    $this->get(route('orders.slip', $order))
        ->assertNotFound()
        ->assertDontSee('private-slip.png', false);
});

test('the tracking token is hidden when the order is serialized', function () {
    $order = Order::factory()->create([
        'tracking_token' => str_repeat('h', 40),
    ]);

    expect($order->tracking_token)->toBe(str_repeat('h', 40))
        ->and($order->toArray())->not->toHaveKey('tracking_token');
});
