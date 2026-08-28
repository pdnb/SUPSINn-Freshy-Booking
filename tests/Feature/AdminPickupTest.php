<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests cannot visit pickup', function () {
    $this->get(route('admin.pickup'))
        ->assertRedirect(route('login'));
});

test('staff can search pickup by student id or name', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRPICK001',
        'student_id' => '67019999999',
        'full_name' => 'นภา รับของ',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRPICK002',
        'student_id' => '67018888888',
        'full_name' => 'อื่น ไม่เกี่ยว',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->assertSee('placeholder="รหัสออเดอร์หรือรหัสนักศึกษา"', false)
        ->assertSee('aria-label="ค้นหาออเดอร์"', false)
        ->assertSee('เคลียร์', false)
        ->assertDontSeeHtml('<label class="field"')
        ->set('search', '67019999999')
        ->assertSee('FRPICK001', false)
        ->assertDontSee('FRPICK002', false)
        ->set('search', 'นภา')
        ->assertSee('FRPICK001', false)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('selectedId', null);
});

test('staff can mark a ready order as picked up', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRPICKUP1',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->set('search', 'FRPICKUP1')
        ->call('select', $order->id)
        ->call('markPickedUp')
        ->assertSee('รับของแล้ว', false);

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->fresh()->receipt_issued_at)->not->toBeNull();
});

test('staff must collect the remaining deposit balance before pickup completion', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->deposit()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRDEP001',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->set('search', 'FRDEP001')
        ->call('select', $order->id)
        ->assertSee('บันทึกเก็บส่วนที่เหลือ', false)
        ->assertDontSeeHtml('wire:click="markPickedUp"')
        ->call('collectBalance')
        ->assertSee('รับของแล้ว', false)
        ->call('markPickedUp');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->fresh()->balance_collected_at)->not->toBeNull();
});

test('a unique pickup search hit is selected for the desk', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRONLY001',
        'full_name' => 'หนึ่ง คนเดียว',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->set('search', 'FRONLY001')
        ->assertSet('selectedId', $order->id)
        ->assertSeeHtml('class="is-clickable is-selected"')
        ->assertSee('หนึ่ง คนเดียว', false)
        ->assertSeeHtml('wire:click="markPickedUp"');
});

test('two pickup search hits stay unselected until staff pick a row', function () {
    $staff = User::factory()->create();
    $first = Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRPAIR001',
        'full_name' => 'คู่ หนึ่ง',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRPAIR002',
        'full_name' => 'คู่ สอง',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->set('search', 'FRPAIR')
        ->assertSet('selectedId', null)
        ->assertSee('เลือกแถวทางซ้าย', false)
        ->assertDontSeeHtml('wire:click="markPickedUp"')
        ->call('select', $first->id)
        ->assertSet('selectedId', $first->id)
        ->assertSeeHtml('class="is-clickable is-selected"')
        ->assertSee('คู่ หนึ่ง', false);
});

test('pickup empty search asks staff to type', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->assertSee('พิมพ์เพื่อค้นหา', false)
        ->assertDontSee('เลือกแถวทางซ้าย', false)
        ->assertSeeHtml('class="fulfill-split"')
        ->assertSee('max-width:420px', false)
        ->assertSeeHtml('class="empty fulfill-empty"')
        ->set('search', 'ไม่มีออเดอร์นี้')
        ->assertSee('ไม่พบออเดอร์', false);
});

test('pickup detail shows guest method items and remaining deposit', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->deposit()->create([
        'status' => OrderStatus::ReadyForPickup,
        'fulfillment' => FulfillmentMethod::Hall,
        'number' => 'FRDESK001',
        'full_name' => 'วิชัย รับของ',
        'student_id' => '67017777777',
        'phone' => '0811111111',
    ]);
    $order->items()->create([
        'product_id' => null,
        'name' => 'ชุดนักศึกษา',
        'price' => 2000,
        'qty' => 1,
        'choices' => [
            ['label' => 'ไซส์เสื้อ', 'value' => 'L'],
        ],
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.pickup')
        ->set('search', 'FRDESK001')
        ->assertSeeHtml('class="is-clickable is-selected"')
        ->assertSeeHtml('<div class="meta">67017777777</div>')
        ->assertSee('วิชัย รับของ', false)
        ->assertSee('0811111111', false)
        ->assertSee('รับที่หอประชุมฯ วันรายงานตัว', false)
        ->assertSeeHtml('class="fulfill-items"')
        ->assertSee('ชุดนักศึกษา × 1', false)
        ->assertSee('ไซส์เสื้อ · L', false)
        ->assertSee('ยอดสุทธิ', false)
        ->assertSee('ต้องเก็บส่วนที่เหลือ', false)
        ->assertSee('ยังไม่เก็บ', false)
        ->assertSee('บันทึกเก็บส่วนที่เหลือ', false)
        ->assertDontSeeHtml('wire:click="markPickedUp"')
        ->assertDontSeeHtml('<label class="field">');
});

test('pickup filter toolbar hides field labels', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.pickup'))
        ->assertOk()
        ->assertSee('aria-label="ค้นหาออเดอร์"', false)
        ->assertSee('placeholder="รหัสออเดอร์หรือรหัสนักศึกษา"', false)
        ->assertSee('max-width:420px', false)
        ->assertSee('เคลียร์', false)
        ->assertDontSeeHtml('<label class="field">');
});

test('fulfillment is a postage queue without channel tabs or pickup orders', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRBOOK001',
        'full_name' => 'สมชาย จุดรับ',
        'student_id' => '67015555555',
        'fulfillment' => FulfillmentMethod::Bookstore,
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRPOST001',
        'full_name' => 'ปิยะ ไปรษณีย์',
        'student_id' => '67016666666',
        'fulfillment' => FulfillmentMethod::Post,
    ]);

    $this->actingAs($staff)
        ->get(route('admin.fulfillment'))
        ->assertOk()
        ->assertSee('คิวพัสดุลหลังแพ็คของแล้ว', false)
        ->assertDontSee('จัดส่ง / จุดรับ', false)
        ->assertDontSee('แยกตามช่องทาง', false)
        ->assertDontSee('role="tablist"', false)
        ->assertDontSee('FRBOOK001', false)
        ->assertDontSee('สมชาย จุดรับ', false)
        ->assertSee('FRPOST001', false)
        ->assertSee('ปิยะ ไปรษณีย์', false)
        ->assertDontSee('wire:click="markReady"', false)
        ->assertDontSee('พร้อมรับของ', false)
        ->assertDontSee('ทั้งหมดในช่องทาง', false)
        ->assertSee('>ทั้งหมด</option>', false);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('id', 'FRBOOK001')
        ->assertDontSee('สมชาย จุดรับ', false)
        ->assertDontSeeHtml('wire:click="markReady"')
        ->assertSee('เลือกแถวทางซ้าย', false);
});

test('the post fulfillment active queue includes shipped orders missing a parcel number', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'number' => 'FRPOST001',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => null,
        'number' => 'FRPOST002',
    ]);
    Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => 'EMS999TH',
        'number' => 'FRPOST003',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->assertSee('FRPOST001', false)
        ->assertSee('FRPOST002', false)
        ->assertDontSee('FRPOST003', false)
        ->assertSee('รอเลขพัสดุ', false);
});

test('staff can mark a postal order shipped with a parcel number', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '123 ถนนทดสอบ',
        'number' => 'FRSHIP001',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('id', $order->number)
        ->set('parcelNumber', 'EMS123456TH')
        ->assertSee('aria-label="เลขพัสดุ"', false)
        ->call('markShipped')
        ->assertDispatched('admin-toast');

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped)
        ->and($order->fresh()->parcel_number)->toBe('EMS123456TH');
});

test('staff can update a parcel number on a shipped postal order', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => 'EMS111',
        'number' => 'FRSHIP002',
        'address' => '123 ถนนทดสอบ',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('status', 'all')
        ->set('id', $order->number)
        ->set('parcelNumber', 'EMS222')
        ->call('saveParcelNumber')
        ->assertDispatched('admin-toast');

    expect($order->fresh()->parcel_number)->toBe('EMS222')
        ->and($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('clearing a parcel number returns the order to the post active queue', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => 'EMS333',
        'number' => 'FRSHIP003',
        'address' => '123 ถนนทดสอบ',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('status', 'all')
        ->set('id', $order->number)
        ->set('parcelNumber', '')
        ->call('saveParcelNumber');

    expect($order->fresh()->parcel_number)->toBeNull();

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->assertSee('FRSHIP003', false);
});

test('fulfillment guest column shows a smaller student id', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRMETA001',
        'full_name' => 'สมชาย ใจดี',
        'student_id' => '67015555555',
        'fulfillment' => FulfillmentMethod::Post,
    ]);

    $this->actingAs($staff)
        ->get(route('admin.fulfillment'))
        ->assertOk()
        ->assertSeeHtml('<div class="meta">67015555555</div>');
});

test('fulfillment filter toolbar hides field labels', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.fulfillment'))
        ->assertOk()
        ->assertSee('aria-label="ค้นหา"', false)
        ->assertSee('aria-label="สถานะ"', false)
        ->assertSee('placeholder="รหัสออเดอร์หรือรหัสนักศึกษา"', false)
        ->assertSee('max-width:360px', false)
        ->assertSee('เคลียร์', false)
        ->assertDontSeeHtml('<label class="field">');
});

test('fulfillment detail shows address items and a selected row', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Post,
        'number' => 'FRDETAIL1',
        'full_name' => 'วิชัย ไปรษณีย์',
        'student_id' => '67017777777',
        'address' => "99 ถนนทดสอบ\nแขวงคลองเตย",
    ]);
    $order->items()->create([
        'product_id' => null,
        'name' => 'ชุดนักศึกษา',
        'price' => 500,
        'qty' => 1,
        'choices' => [
            ['label' => 'ไซส์เสื้อ', 'value' => 'L'],
        ],
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->assertSee('เลือกแถวทางซ้าย', false)
        ->set('id', $order->number)
        ->assertSeeHtml('class="is-clickable is-selected"')
        ->assertSee('วิชัย ไปรษณีย์', false)
        ->assertSee('67017777777', false)
        ->assertSee('99 ถนนทดสอบ', false)
        ->assertSeeHtml('class="fulfill-items"')
        ->assertDontSeeHtml('class="choice-list fulfill-items"')
        ->assertSee('ชุดนักศึกษา × 1', false)
        ->assertSee('ไซส์เสื้อ · L', false)
        ->assertSeeHtml('class="choice-list"')
        ->assertSee('aria-label="เลขพัสดุ"', false)
        ->assertSee('จัดส่งแล้ว', false)
        ->assertDontSeeHtml('<label class="field">');
});

test('staff can clear fulfillment filters', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('status', 'all')
        ->set('search', 'FRTEST')
        ->set('id', 'FRTEST')
        ->call('clearFilters')
        ->assertSet('status', 'active')
        ->assertSet('search', '')
        ->assertSet('id', '');
});

test('dashboard recent links send postage to fulfillment and pickup to the desk', function () {
    $staff = User::factory()->create();
    $post = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRDASHPOST',
        'fulfillment' => FulfillmentMethod::Post,
        'created_at' => now()->subMinute(),
    ]);
    $ready = Order::factory()->create([
        'status' => OrderStatus::ReadyForPickup,
        'number' => 'FRDASHPICK',
        'fulfillment' => FulfillmentMethod::Hall,
        'created_at' => now()->subSeconds(30),
    ]);
    $confirmedPickup = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRDASHBOOK',
        'fulfillment' => FulfillmentMethod::Bookstore,
        'created_at' => now(),
    ]);

    $this->actingAs($staff)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('admin.fulfillment', ['id' => $post->number]).'"', false)
        ->assertSee('href="'.route('admin.pickup', ['search' => $ready->number]).'"', false)
        ->assertSee('href="'.route('admin.orders.show', $confirmedPickup).'"', false);
});
