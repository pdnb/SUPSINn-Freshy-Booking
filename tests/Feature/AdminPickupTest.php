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
        ->assertSee('ล้างตัวกรอง', false)
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

test('fulfillment has no ready for pickup button on bookstore orders', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRFULFILL',
        'full_name' => 'สมชาย ใจดี',
        'student_id' => '67015555555',
        'fulfillment' => FulfillmentMethod::Bookstore,
    ]);

    $this->actingAs($staff)
        ->get(route('admin.fulfillment'))
        ->assertOk()
        ->assertSee('สมชาย ใจดี', false)
        ->assertSee('67015555555', false)
        ->assertDontSee('wire:click="markReady"', false)
        ->assertDontSee('พร้อมรับของ', false);

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('id', $order->number)
        ->assertDontSeeHtml('wire:click="markReady"');

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
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
        ->set('channel', FulfillmentMethod::Post->value)
        ->assertSee('FRPOST001', false)
        ->assertSee('FRPOST002', false)
        ->assertDontSee('FRPOST003', false);
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
        ->set('channel', FulfillmentMethod::Post->value)
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
        ->set('channel', FulfillmentMethod::Post->value)
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
        ->set('channel', FulfillmentMethod::Post->value)
        ->set('status', 'all')
        ->set('id', $order->number)
        ->set('parcelNumber', '')
        ->call('saveParcelNumber');

    expect($order->fresh()->parcel_number)->toBeNull();

    Livewire::actingAs($staff)
        ->test('pages::admin.fulfillment')
        ->set('channel', FulfillmentMethod::Post->value)
        ->assertSee('FRSHIP003', false);
});

test('fulfillment guest column shows a smaller student id', function () {
    $staff = User::factory()->create();
    Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'number' => 'FRMETA001',
        'full_name' => 'สมชาย ใจดี',
        'student_id' => '67015555555',
        'fulfillment' => FulfillmentMethod::Bookstore,
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
        ->assertSee('ล้างตัวกรอง', false)
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
