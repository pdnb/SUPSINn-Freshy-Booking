<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests cannot visit the slip queue', function () {
    $this->get(route('admin.orders'))
        ->assertRedirect(route('login'));
});

test('staff can see pending review orders in the slip queue', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRQUEUE99',
        'full_name' => 'ผู้จองทดสอบ',
        'created_at' => now()->setTimezone(config('app.timezone'))->setTime(14, 30),
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRHIDDEN1',
    ]);

    $this->actingAs($staff)
        ->get(route('admin.orders'))
        ->assertOk()
        ->assertSee('ออเดอร์', false)
        ->assertSee('วันที่', false)
        ->assertSee($order->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i'), false)
        ->assertSee('aria-label="ค้นหา"', false)
        ->assertDontSeeHtml('<label class="field">')
        ->assertSee('ล้างตัวกรอง', false)
        ->assertSee('FRQUEUE99', false)
        ->assertDontSee('FRHIDDEN1', false);
});

test('staff can confirm a slip and move to the next queue order', function () {
    $staff = User::factory()->create();
    $first = Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRFIRST01',
    ]);
    $second = Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRNEXT001',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.order-detail', ['order' => $first])
        ->call('confirm')
        ->assertRedirect(route('admin.orders.show', $second));

    expect($first->fresh()->status)->toBe(OrderStatus::Confirmed);
});

test('staff must confirm before cancelling an order', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRCANCEL1',
        'full_name' => 'ผู้จองยกเลิก',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.order-detail', ['order' => $order])
        ->call('openCancelConfirm')
        ->assertSet('showCancelConfirm', true)
        ->assertSee('ต้องการยกเลิกออเดอร์', false)
        ->assertSee('FRCANCEL1', false)
        ->call('closeCancelConfirm')
        ->assertSet('showCancelConfirm', false);

    expect($order->fresh()->status)->toBe(OrderStatus::PendingReview);

    Livewire::actingAs($staff)
        ->test('pages::admin.order-detail', ['order' => $order])
        ->call('openCancelConfirm')
        ->call('cancel')
        ->assertRedirect(route('admin.orders'));

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

test('staff can filter the queue by status', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::NeedReslip,
        'number' => 'FRRESLIP1',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.order-queue')
        ->set('status', OrderStatus::NeedReslip->value)
        ->assertSee('FRRESLIP1', false);
});

test('staff can filter the queue by date range', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FROLD001',
        'created_at' => now()->timezone(config('app.timezone'))->subDays(3)->setTime(9, 0),
    ]);
    Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRTODAY1',
        'created_at' => now()->timezone(config('app.timezone'))->setTime(12, 0),
    ]);

    $today = now()->timezone(config('app.timezone'))->toDateString();

    Livewire::actingAs($staff)
        ->test('pages::admin.order-queue')
        ->set('date_from', $today)
        ->set('date_to', $today)
        ->assertSee('FRTODAY1', false)
        ->assertDontSee('FROLD001', false)
        ->call('clearFilters')
        ->assertSet('date_from', '')
        ->assertSet('date_to', '')
        ->assertSee('FROLD001', false);
});

test('filter action buttons line up with labeled inputs', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.filters\s*\{[^}]*align-items:\s*flex-end/s');
});

test('slip preview frame is tall enough for portrait screenshots', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.slip-frame\s*\{[^}]*min-height:\s*640px/s');
});

test('order detail lists each choice on its own line', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRCHOICE1',
    ]);
    $order->items()->create([
        'product_id' => null,
        'name' => 'ชุดนักศึกษา',
        'price' => 500,
        'qty' => 1,
        'choices' => [
            ['label' => 'เพศ / แบบเสื้อ', 'value' => 'ชาย'],
            ['label' => 'ไซส์เสื้อ', 'value' => '2XL'],
        ],
    ]);

    $html = $this->actingAs($staff)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('เพศ / แบบเสื้อ · ชาย', false)
        ->assertSee('ไซส์เสื้อ · 2XL', false)
        ->assertDontSee('เพศ / แบบเสื้อ ชาย, ไซส์เสื้อ 2XL', false)
        ->getContent();

    expect($html)->toContain('class="choice-list"');
});

test('order detail summary shows full guest and money breakdown', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::PendingReview,
        'number' => 'FRDETAIL1',
        'full_name' => 'สมชาย ใจดี',
        'student_id' => '67019999999',
        'faculty' => 'คณะวิทยาการจัดการ',
        'phone' => '0899999999',
        'fulfillment' => FulfillmentMethod::Post,
        'address' => "บ้านเลขที่ 1\nต.ขุนทะเล",
        'shipping_rate_name' => 'EMS',
        'subtotal' => '1500.00',
        'shipping_amount' => '90.00',
        'total' => '1590.00',
    ]);
    $order->items()->create([
        'product_id' => null,
        'name' => 'SRU คอมโบเซ็ต',
        'price' => 1500,
        'qty' => 1,
        'choices' => [
            ['label' => 'ชุดนักศึกษา · ไซส์เสื้อ', 'value' => 'XL'],
        ],
    ]);

    $this->actingAs($staff)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('ผู้จอง', false)
        ->assertSee('สมชาย ใจดี', false)
        ->assertSee('67019999999', false)
        ->assertSee('คณะวิทยาการจัดการ', false)
        ->assertSee('0899999999', false)
        ->assertSee('จัดส่งทางไปรษณีย์', false)
        ->assertSee('บ้านเลขที่ 1', false)
        ->assertSee('ชุดนักศึกษา · ไซส์เสื้อ · XL', false)
        ->assertSee('ยอดสินค้า', false)
        ->assertSee('ค่าส่ง · EMS', false)
        ->assertSee('1,500', false)
        ->assertSee('90', false)
        ->assertSee('1,590', false);
});
