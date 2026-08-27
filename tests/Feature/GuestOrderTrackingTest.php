<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\SlipVerificationResult;
use App\Models\Order;
use App\Models\PaymentSlip;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        ->assertSee('รหัสออเดอร์', false)
        ->assertSee('คัดลอกรหัสออเดอร์ FRTRACK01', false)
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
        ->assertSee('rounded-brand', false)
        ->assertSee('border-border', false)
        ->assertSee('bg-surface', false);

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

test('order service looks up guest orders by exact student id and phone', function () {
    Order::factory()->create([
        'number' => 'FRLOOK001',
        'student_id' => '67019999999',
        'phone' => '0891111111',
    ]);
    Order::factory()->create([
        'number' => 'FRLOOK002',
        'student_id' => '67019999999',
        'phone' => '0891111111',
    ]);
    Order::factory()->create([
        'number' => 'FRSKIP001',
        'student_id' => '67019999999',
        'phone' => '0892222222',
    ]);
    Order::factory()->create([
        'number' => 'FRSKIP002',
        'student_id' => '67018888888',
        'phone' => '0891111111',
    ]);

    $orders = app(OrderService::class)->findForGuestLookup('67019999999', '0891111111');

    expect($orders->pluck('number')->all())->toEqualCanonicalizing(['FRLOOK001', 'FRLOOK002']);
});

test('the orders page shows a lookup form when the guest has no tracking link', function () {
    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ค้นหาคำสั่งซื้อ', false)
        ->assertSee('รหัสนักศึกษา', false)
        ->assertSee('เบอร์โทรศัพท์', false)
        ->assertSee('ค้นหา', false)
        ->assertSeeHtml('wire:click="search"')
        ->assertDontSee('ยังไม่มีคำสั่งซื้อ', false)
        ->assertDontSee('โทเคนติดตาม', false);
});

test('remembered guest tracking reopens the order from the orders tab', function () {
    $order = Order::factory()->create([
        'number' => 'FRSESSION',
        'tracking_token' => str_repeat('j', 40),
    ]);

    app(OrderService::class)->rememberGuestTracking($order, autoOpen: true);

    $this->get(route('orders.index'))
        ->assertRedirect(route('orders.confirmation', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]));
});

test('viewing a looked up order returns to the search form on the orders tab', function () {
    $order = Order::factory()->create([
        'number' => 'FRLOOKUP1',
        'tracking_token' => str_repeat('l', 40),
        'student_id' => '67016666666',
        'phone' => '0866666666',
    ]);

    Livewire::test('pages::storefront.order-track')
        ->set('student_id', '67016666666')
        ->set('phone', '0866666666')
        ->call('search')
        ->assertSee('FRLOOKUP1');

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))->assertOk();

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ค้นหาคำสั่งซื้อ', false)
        ->assertDontSee('FRLOOKUP1', false);
});

test('the post booking orders tab shortcut only redirects once', function () {
    $order = Order::factory()->create([
        'number' => 'FRBOOK001',
        'tracking_token' => str_repeat('b', 40),
    ]);

    app(OrderService::class)->rememberGuestTracking($order, autoOpen: true);

    $this->get(route('orders.index'))
        ->assertRedirect(route('orders.confirmation', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]));

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ค้นหาคำสั่งซื้อ', false);
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

test('the confirmation page shows a thai receipt issued datetime', function () {
    $issuedAt = now()->timezone(config('app.timezone'))->setTime(14, 30);
    $order = Order::factory()->create([
        'number' => 'FRRECEIPT',
        'tracking_token' => str_repeat('s', 40),
        'receipt_issued_at' => $issuedAt,
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('ออกใบเสร็จแล้ว '.$issuedAt->toThaiDatetime(), false);
});

test('a postal confirmation uses three shipped steps and hides the pickup receipt note', function () {
    $order = Order::factory()->create([
        'number' => 'FRPOSTTRK',
        'tracking_token' => str_repeat('t', 40),
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => 'EMS123456TH',
        'address' => '123 ถนนทดสอบ',
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('จองแล้ว', false)
        ->assertSee('ตรวจสลิป', false)
        ->assertSee('จัดส่งแล้ว', false)
        ->assertDontSee('พร้อมรับ', false)
        ->assertDontSee('รับแล้ว', false)
        ->assertSee('เลขพัสดุ', false)
        ->assertSee('คัดลอกเลขพัสดุ EMS123456TH', false)
        ->assertSee('grid-cols-3', false)
        ->assertDontSee('ใบเสร็จจะได้รับตอนรับสินค้า', false)
        ->assertDontSee('จุดรับสินค้า', false)
        ->assertDontSee(FulfillmentMethod::Bookstore->label(), false)
        ->assertDontSee(FulfillmentMethod::Hall->label(), false);
});

test('a shipped postal order without a parcel number does not show a copy control', function () {
    $order = Order::factory()->create([
        'number' => 'FRPOSTNON',
        'tracking_token' => str_repeat('v', 40),
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => null,
        'address' => '123 ถนนทดสอบ',
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('จัดส่งแล้ว', false)
        ->assertDontSee('คัดลอกเลขพัสดุ', false);
});

test('a pickup confirmation keeps four steps and does not show a parcel number', function () {
    $order = Order::factory()->create([
        'number' => 'FRPICKTRK',
        'tracking_token' => str_repeat('u', 40),
        'status' => OrderStatus::ReadyForPickup,
        'fulfillment' => FulfillmentMethod::Bookstore,
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('จองแล้ว', false)
        ->assertSee('ตรวจสลิป', false)
        ->assertSee('พร้อมรับ', false)
        ->assertSee('รับแล้ว', false)
        ->assertSee('ใบเสร็จจะได้รับตอนรับสินค้า', false)
        ->assertDontSee('คัดลอกเลขพัสดุ', false)
        ->assertDontSee('จัดส่งแล้ว', false);
});

test('a pickup confirmation shows only the ordered pickup point', function (FulfillmentMethod $method, FulfillmentMethod $other) {
    $order = Order::factory()->create([
        'number' => $method === FulfillmentMethod::Bookstore ? 'FRPNTBOOK' : 'FRPNTHALL',
        'tracking_token' => str_repeat($method === FulfillmentMethod::Bookstore ? 'w' : 'x', 40),
        'status' => OrderStatus::ReadyForPickup,
        'fulfillment' => $method,
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('จุดรับสินค้า', false)
        ->assertSee($method->label(), false)
        ->assertSee($method->caption(), false)
        ->assertDontSee($other->label(), false)
        ->assertDontSee($other->caption(), false);
})->with([
    'bookstore' => [FulfillmentMethod::Bookstore, FulfillmentMethod::Hall],
    'hall' => [FulfillmentMethod::Hall, FulfillmentMethod::Bookstore],
]);

test('a guest can list matching orders by student id and phone', function () {
    $mineA = Order::factory()->create([
        'number' => 'FRFIND001',
        'student_id' => '67017777777',
        'phone' => '0811111111',
        'status' => OrderStatus::PendingReview,
        'total' => '350.00',
        'full_name' => 'สมชาย หาเจอ',
    ]);
    $mineB = Order::factory()->create([
        'number' => 'FRFIND002',
        'student_id' => '67017777777',
        'phone' => '0811111111',
        'status' => OrderStatus::Confirmed,
        'total' => '700.00',
    ]);
    Order::factory()->create([
        'number' => 'FROTHER01',
        'student_id' => '67017777777',
        'phone' => '0822222222',
        'full_name' => 'ไม่ควรเห็นชื่อนี้',
    ]);
    Order::factory()->create([
        'number' => 'FROTHER02',
        'student_id' => '67016666666',
        'phone' => '0811111111',
        'full_name' => 'ออเดอร์คนอื่น',
    ]);

    Livewire::test('pages::storefront.order-track')
        ->set('student_id', '67017777777')
        ->set('phone', '081-111-1111')
        ->call('search')
        ->assertHasNoErrors()
        ->assertSee('ออเดอร์ของฉัน')
        ->assertSee('FRFIND001')
        ->assertSee('FRFIND002')
        ->assertSee('รอเจ้าหน้าที่ตรวจสลิป')
        ->assertSee('ยืนยันการชำระแล้ว')
        ->assertDontSee('FROTHER01')
        ->assertDontSee('ไม่ควรเห็นชื่อนี้')
        ->assertDontSee('FROTHER02')
        ->assertDontSee('ออเดอร์คนอื่น')
        ->assertSee(route('orders.confirmation', [
            'order' => $mineA,
            'token' => $mineA->tracking_token,
        ]))
        ->assertSee(route('orders.confirmation', [
            'order' => $mineB,
            'token' => $mineB->tracking_token,
        ]));
});

test('a mismatched student id or phone does not reveal orders', function () {
    Order::factory()->create([
        'number' => 'FRSECRET2',
        'student_id' => '67015555555',
        'phone' => '0833333333',
        'full_name' => 'ห้ามโชว์ชื่อนี้',
    ]);

    Livewire::test('pages::storefront.order-track')
        ->set('student_id', '67015555555')
        ->set('phone', '0844444444')
        ->call('search')
        ->assertHasErrors(['lookup'])
        ->assertSee('ไม่พบคำสั่งซื้อที่ตรงกับข้อมูลนี้')
        ->assertDontSee('FRSECRET2')
        ->assertDontSee('ห้ามโชว์ชื่อนี้');
});

test('guest order lookup rejects invalid student id and phone formats', function () {
    Livewire::test('pages::storefront.order-track')
        ->set('student_id', '123')
        ->set('phone', '0812345678')
        ->call('search')
        ->assertHasErrors(['student_id'])
        ->assertSee('รหัสนักศึกษาไม่ถูกต้อง');

    Livewire::test('pages::storefront.order-track')
        ->set('student_id', '67011234567')
        ->set('phone', '912345678')
        ->call('search')
        ->assertHasErrors(['phone'])
        ->assertSee('เบอร์โทรศัพท์ไม่ถูกต้อง');
});

test('guest order lookup is rate limited after too many failures', function () {
    Order::factory()->create([
        'student_id' => '67014444444',
        'phone' => '0855555555',
        'number' => 'FRHIDDEN1',
    ]);

    $page = Livewire::test('pages::storefront.order-track')
        ->set('student_id', '67014444444')
        ->set('phone', '0866666666');

    foreach (range(1, 5) as $attempt) {
        $page->call('search')->assertHasErrors('lookup');
    }

    $page->call('search')->assertHasErrors('lookup');

    expect($page->errors()->first('lookup'))->toContain('ลองใหม่')
        ->and($page->html())->not->toContain('FRHIDDEN1');
});

test('the tracking token is hidden when the order is serialized', function () {
    $order = Order::factory()->create([
        'tracking_token' => str_repeat('h', 40),
    ]);

    expect($order->tracking_token)->toBe(str_repeat('h', 40))
        ->and($order->toArray())->not->toHaveKey('tracking_token');
});

function guestNeedReslipOrder(array $attributes = []): Order
{
    $order = Order::factory()->create(array_merge([
        'number' => 'FRRESLIP1',
        'tracking_token' => str_repeat('r', 40),
        'status' => OrderStatus::NeedReslip,
    ], $attributes));

    $path = 'slips/'.$order->id.'/old-slip.jpg';
    Storage::disk('local')->put($path, 'old slip bytes');

    $order->slip()->create([
        'path' => $path,
        'original_name' => 'old-slip.jpg',
        'checksum' => hash('sha256', 'old slip bytes'),
        'verifier_result' => SlipVerificationResult::Pass,
    ]);

    return $order->fresh(['slip']);
}

test('a need reslip confirmation shows promptpay and resubmit controls', function () {
    Storage::fake('local');
    $order = guestNeedReslipOrder();

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('ต้องแนบสลิปใหม่', false)
        ->assertSee('PromptPay', false)
        ->assertSee('data:image/svg+xml', false)
        ->assertSee('แนบสลิปการโอน', false)
        ->assertSee('ส่งสลิปใหม่', false)
        ->assertSeeHtml('wire:click="resubmit"')
        ->assertDontSee('สลิปผ่านการตรวจเบื้องต้นแล้ว', false)
        ->assertDontSee('ดูสลิป old-slip.jpg', false);
});

test('a deposit need reslip confirmation shows the amount due now on the qr', function () {
    Storage::fake('local');
    $order = guestNeedReslipOrder([
        'payment_mode' => PaymentMode::Deposit,
        'total' => '1050.00',
        'amount_due_now' => '500.00',
        'amount_remaining' => '550.00',
    ]);

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('500.00', false)
        ->assertSee('คงเหลือตอนรับ', false)
        ->assertSee('550.00', false);
});

test('a guest can resubmit a slip from the confirmation page', function () {
    Storage::fake('local');
    $order = guestNeedReslipOrder();

    Livewire::test('pages::storefront.order-confirmation', [
        'order' => $order->number,
        'token' => $order->tracking_token,
    ])
        ->set('slip', UploadedFile::fake()->createWithContent('new-slip.jpg', random_bytes(128)))
        ->call('resubmit')
        ->assertHasNoErrors()
        ->assertSee('รอเจ้าหน้าที่ตรวจสลิป', false)
        ->assertSee('สลิปผ่านการตรวจเบื้องต้นแล้ว', false)
        ->assertDontSee('ส่งสลิปใหม่', false);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::PendingReview)
        ->and($order->slip->original_name)->toBe('new-slip.jpg');
});
