<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\BookingRound;
use App\Models\Order;
use App\Models\User;
use App\Services\Packing\PackingChecklistExporter;
use App\Services\Packing\PackingChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function packingChecklist(): PackingChecklistService
{
    return app(PackingChecklistService::class);
}

function packingExporter(): PackingChecklistExporter
{
    return app(PackingChecklistExporter::class);
}

/**
 * @param  array<string, mixed>  $overrides
 * @param  list<array{label: string, value: string}>  $choices
 */
function packableOrder(array $overrides = [], array $choices = [['label' => 'ไซส์เสื้อ', 'value' => 'M']]): Order
{
    $order = Order::factory()->create(array_merge([
        'status' => OrderStatus::Confirmed,
        'fulfillment' => FulfillmentMethod::Bookstore,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], $overrides));

    $order->items()->create([
        'name' => 'คอมโบ ปี 70',
        'price' => '1290.00',
        'qty' => 1,
        'choices' => $choices,
    ]);

    return $order->refresh()->load('items');
}

test('it includes confirmed ready for pickup and post shipped without a parcel number', function () {
    $confirmed = packableOrder(['number' => 'PACKCONF01', 'status' => OrderStatus::Confirmed]);
    $ready = packableOrder([
        'number' => 'PACKREADY1',
        'status' => OrderStatus::ReadyForPickup,
    ]);
    $awaitingParcel = packableOrder([
        'number' => 'PACKPOST01',
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '1 ถนนหลัก',
        'parcel_number' => null,
    ]);

    $numbers = packingChecklist()->orders()->pluck('number');

    expect($numbers)->toContain($confirmed->number, $ready->number, $awaitingParcel->number);
});

test('it excludes pending reslip cancelled completed and shipped with a parcel number', function () {
    packableOrder(['number' => 'PACKPEND01', 'status' => OrderStatus::PendingReview]);
    packableOrder(['number' => 'PACKSLIP01', 'status' => OrderStatus::NeedReslip]);
    packableOrder(['number' => 'PACKCANC01', 'status' => OrderStatus::Cancelled]);
    packableOrder(['number' => 'PACKDONE01', 'status' => OrderStatus::Completed]);
    packableOrder([
        'number' => 'PACKSHIP01',
        'status' => OrderStatus::Shipped,
        'fulfillment' => FulfillmentMethod::Post,
        'parcel_number' => 'TH1234567890',
    ]);

    expect(packingChecklist()->orders())->toHaveCount(0);
});

test('it filters by booking round channel and faculty', function () {
    $roundA = BookingRound::factory()->create();
    $roundB = BookingRound::factory()->create();

    $match = packableOrder([
        'number' => 'PACKFILT01',
        'booking_round_id' => $roundA->id,
        'fulfillment' => FulfillmentMethod::Post,
        'faculty' => 'คณะครุศาสตร์',
        'address' => 'บ้านเลขที่ 9',
    ]);
    packableOrder([
        'number' => 'PACKFILT02',
        'booking_round_id' => $roundB->id,
        'fulfillment' => FulfillmentMethod::Post,
        'faculty' => 'คณะครุศาสตร์',
        'address' => 'บ้านเลขที่ 8',
    ]);
    packableOrder([
        'number' => 'PACKFILT03',
        'booking_round_id' => $roundA->id,
        'fulfillment' => FulfillmentMethod::Hall,
        'faculty' => 'คณะครุศาสตร์',
    ]);
    packableOrder([
        'number' => 'PACKFILT04',
        'booking_round_id' => $roundA->id,
        'fulfillment' => FulfillmentMethod::Post,
        'faculty' => 'คณะนิติศาสตร์',
        'address' => 'บ้านเลขที่ 7',
    ]);

    $numbers = packingChecklist()->orders([
        'booking_round_id' => $roundA->id,
        'fulfillment' => 'post',
        'faculty' => 'คณะครุศาสตร์',
    ])->pluck('number');

    expect($numbers->all())->toBe([$match->number]);
});

test('it sorts post then bookstore then hall then faculty then number', function () {
    packableOrder([
        'number' => 'PACK-H-2',
        'fulfillment' => FulfillmentMethod::Hall,
        'faculty' => 'คณะครุศาสตร์',
    ]);
    packableOrder([
        'number' => 'PACK-B-2',
        'fulfillment' => FulfillmentMethod::Bookstore,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ]);
    packableOrder([
        'number' => 'PACK-P-2',
        'fulfillment' => FulfillmentMethod::Post,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'address' => 'ที่อยู่ 2',
    ]);
    packableOrder([
        'number' => 'PACK-P-1',
        'fulfillment' => FulfillmentMethod::Post,
        'faculty' => 'คณะครุศาสตร์',
        'address' => 'ที่อยู่ 1',
    ]);
    packableOrder([
        'number' => 'PACK-B-1',
        'fulfillment' => FulfillmentMethod::Bookstore,
        'faculty' => 'คณะครุศาสตร์',
    ]);

    expect(packingChecklist()->orders()->pluck('number')->all())->toBe([
        'PACK-P-1',
        'PACK-P-2',
        'PACK-B-1',
        'PACK-B-2',
        'PACK-H-2',
    ]);
});

test('the pdf lists guest fields choices and checkboxes without prices', function () {
    packableOrder([
        'number' => 'PACKPDF001',
        'full_name' => 'สมศรี แพ็คของ',
        'student_id' => '67019990001',
        'phone' => '0811111111',
        'faculty' => 'คณะครุศาสตร์',
        'major' => 'ภาษาไทย',
        'fulfillment' => FulfillmentMethod::Post,
        'address' => "99 ถนนหลัก\nอ.เมือง",
    ], [
        ['label' => 'ไซส์เสื้อ', 'value' => 'XL'],
    ]);

    $orders = packingChecklist()->orders();

    $html = view('admin.packing-checklist.pdf', [
        'orders' => $orders,
        'barcodes' => packingExporter()->barcodes($orders),
        'qrs' => packingExporter()->qrs($orders),
        'roundName' => 'ทุกรอบ',
        'faculty' => 'ทุกคณะ',
        'channelLabel' => 'ทุกช่องทาง',
        'fontPath' => str_replace('\\', '/', resource_path('fonts/Sarabun-Regular.ttf')),
    ])->render();

    expect($html)->toContain('PACKPDF001')
        ->and($html)->toContain('data:image/png;base64')
        ->and($html)->toContain('class="qr"')
        ->and($html)->toContain('class="barcode"')
        ->and($html)->toContain('สมศรี แพ็คของ')
        ->and($html)->toContain('67019990001')
        ->and($html)->toContain('0811111111')
        ->and($html)->toContain('คณะครุศาสตร์')
        ->and($html)->toContain('ภาษาไทย')
        ->and($html)->toContain('ไซส์เสื้อ · XL')
        ->and($html)->toContain('รายการ')
        ->and($html)->toContain('ตัวเลือก')
        ->and($html)->toContain('จำนวน')
        ->and($html)->toMatch('/\.items th\s*\{[^}]*font-weight:\s*normal/s')
        ->and($html)->toContain('class="tick"')
        ->and($html)->not->toContain('1290')
        ->and($html)->not->toContain('ยอดสุทธิ')
        ->and($html)->not->toContain('ใบแพ็ค')
        ->and($html)->not->toContain('แพ็คสินค้า');

    $pdf = TestResponse::fromBaseResponse(packingExporter()->pdf());
    expect($pdf->headers->get('content-disposition'))->toContain('.pdf')
        ->and(packingExporter()->filename())->toContain('แพ็คของ')
        ->and(substr($pdf->getContent(), 0, 4))->toBe('%PDF');
});

test('an empty filter still downloads a pdf', function () {
    $pdf = TestResponse::fromBaseResponse(packingExporter()->pdf());

    expect($pdf->headers->get('content-disposition'))->toContain('.pdf')
        ->and(packingExporter()->filename())->toContain('แพ็คของ')
        ->and(substr($pdf->getContent(), 0, 4))->toBe('%PDF');
});

test('guests and pending users cannot visit packing or export', function () {
    $this->get(route('admin.packing-checklist'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.packing-checklist.export'))
        ->assertRedirect(route('login'));

    $pending = User::factory()->pending()->create();

    $this->actingAs($pending)
        ->get(route('admin.packing-checklist'))
        ->assertRedirect(route('admin.pending'));

    $this->actingAs($pending)
        ->get(route('admin.packing-checklist.export'))
        ->assertRedirect(route('admin.pending'));
});

test('staff can download the packing pdf', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist.export'))
        ->assertOk();
});

test('staff can open packing checklist and clear filters', function () {
    $staff = User::factory()->create();
    packableOrder([
        'number' => 'PACKPAGE01',
        'full_name' => 'นภา ทดสอบ',
        'student_id' => '67018880001',
    ]);

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist'))
        ->assertOk()
        ->assertSee('แพ็คของ', false)
        ->assertDontSee('ใบแพ็ค', false)
        ->assertDontSee('แพ็คสินค้า', false)
        ->assertSee('พิมพ์ checklist หนึ่งออเดอร์ต่อหน้า สำหรับโต๊ะแพ็ค', false)
        ->assertSee('aria-label="รหัสออเดอร์"', false)
        ->assertSee('packing-scan-bar', false)
        ->assertDontSee('filters-align-start', false)
        ->assertDontSeeHtml('<label class="field">')
        ->assertDontSee('PACKPAGE01', false)
        ->assertDontSee('PDF ·', false)
        ->assertSee('แพ็คแล้ววันนี้', false)
        ->assertSee('packing-scan', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee("wire:click=\"\$set('tab', 'print')\"", false)
        ->assertDontSeeHtml('class="kpi"');

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist', ['tab' => 'print']))
        ->assertOk()
        ->assertSee('aria-label="รอบจอง"', false)
        ->assertSee('aria-label="ช่องทาง"', false)
        ->assertSee('aria-label="คณะ"', false)
        ->assertSee('เคลียร์', false)
        ->assertSee('PACKPAGE01', false)
        ->assertSee('นภา ทดสอบ', false)
        ->assertSee('67018880001', false)
        ->assertSee('PDF', false)
        ->assertSee('PDF · 1 ใบ', false)
        ->assertSee('ออเดอร์', false)
        ->assertDontSee('packing-scan', false)
        ->assertDontSeeHtml('class="kpi"');

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->assertSet('tab', 'scan')
        ->set('tab', 'print')
        ->set('booking_round_id', '1')
        ->set('fulfillment', 'post')
        ->set('faculty', 'คณะวิศวกรรมศาสตร์')
        ->call('clearFilters')
        ->assertSet('booking_round_id', '')
        ->assertSet('fulfillment', '')
        ->assertSet('faculty', '');
});

test('pack stays attached to the scan bar when an error appears', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.packing-scan-bar\s*\{[^}]*display:\s*flex/s')
        ->and($css)->toMatch('/\.packing-scan-bar \.input\s*\{[^}]*min-height:\s*56px/s')
        ->and($css)->toMatch('/\.packing-scan-bar \.btn\s*\{[^}]*min-height:\s*56px/s');

    $staff = User::factory()->create();
    $order = packableOrder(['number' => 'PACKALIGN1']);
    packingChecklist()->markPacked($order->number, $staff);

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->set('packNumber', $order->number)
        ->call('markPacked')
        ->assertSee('ออเดอร์นี้แพ็คแล้ว', false)
        ->assertSeeHtml('packing-scan-bar is-invalid');
});

test('the packing station shows empty copy when the pile is empty', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist'))
        ->assertOk()
        ->assertSee('ยังไม่มีออเดอร์ที่แพ็ควันนี้', false)
        ->assertDontSee('ไม่มีออเดอร์ในตัวกรองนี้', false)
        ->assertDontSee('PDF ·', false);

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist', ['tab' => 'print']))
        ->assertOk()
        ->assertSee('ไม่มีออเดอร์ในตัวกรองนี้', false)
        ->assertSee('PDF · 0 ใบ', false)
        ->assertSee('ออเดอร์', false);
});

test('an unknown tab query falls back to scan', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist', ['tab' => 'nope']))
        ->assertOk()
        ->assertSee('packing-scan', false)
        ->assertDontSee('PDF ·', false);

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->set('tab', 'nope')
        ->assertSet('tab', 'scan');
});

test('the packing station groups channels shows qty and packed time', function () {
    $staff = User::factory()->create();

    $post = packableOrder([
        'number' => 'PACKUIPOST',
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '1 ถนนหลัก',
        'faculty' => 'คณะครุศาสตร์',
    ]);
    $post->items()->update(['qty' => 3]);

    packableOrder([
        'number' => 'PACKUIBOOK',
        'fulfillment' => FulfillmentMethod::Bookstore,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ]);

    $packed = packableOrder([
        'number' => 'PACKUIDONE',
        'full_name' => 'แพ็ค เสร็จแล้ว',
        'fulfillment' => FulfillmentMethod::Hall,
    ]);
    packingChecklist()->markPacked($packed->number, $staff);

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist'))
        ->assertOk()
        ->assertSee('แพ็ค เสร็จแล้ว', false)
        ->assertSee('น.', false)
        ->assertSee('aria-label="ยกเลิกแพ็ค PACKUIDONE"', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertDontSee('PDF ·', false);

    $this->actingAs($staff)
        ->get(route('admin.packing-checklist', ['tab' => 'print']))
        ->assertOk()
        ->assertSee('PDF · 2 ใบ', false)
        ->assertSee('packing-group', false)
        ->assertSee('จัดส่งทางไปรษณีย์', false)
        ->assertSee('รับที่ศูนย์หนังสือและเอกสารตำรา', false)
        ->assertSeeHtml('<td class="num-col">3</td>')
        ->assertDontSee('แพ็ค เสร็จแล้ว', false);
});

test('marking packed drops the order from the print pile and unmarking restores it', function () {
    $staff = User::factory()->create();
    $order = packableOrder(['number' => 'PACKMARK01']);

    packingChecklist()->markPacked('  PACKMARK01  ', $staff);

    $packed = $order->fresh();

    expect($packed->packed_at)->not->toBeNull()
        ->and($packed->status)->toBe(OrderStatus::ReadyForPickup)
        ->and(packingChecklist()->orders()->pluck('number'))->not->toContain($order->number)
        ->and(packingChecklist()->packedToday()->pluck('number'))->toContain($order->number);

    packingChecklist()->unmarkPacked($order->number, $staff);

    expect($order->fresh()->packed_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and(packingChecklist()->orders()->pluck('number'))->toContain($order->number);
});

test('packing a bookstore or hall order marks it ready for pickup', function (FulfillmentMethod $channel) {
    $staff = User::factory()->create();
    $order = packableOrder([
        'number' => 'PACKREADY'.$channel->value,
        'fulfillment' => $channel,
    ]);

    packingChecklist()->markPacked($order->number, $staff);

    expect($order->fresh()->status)->toBe(OrderStatus::ReadyForPickup)
        ->and($order->fresh()->packed_at)->not->toBeNull();

    $this->get(route('orders.confirmation', [
        'order' => $order,
        'token' => $order->tracking_token,
    ]))
        ->assertOk()
        ->assertSee('พร้อมรับ', false);
})->with([
    'bookstore' => FulfillmentMethod::Bookstore,
    'hall' => FulfillmentMethod::Hall,
]);

test('packing a postal order does not change guest-visible status', function () {
    $staff = User::factory()->create();
    $order = packableOrder([
        'number' => 'PACKPOSTST',
        'fulfillment' => FulfillmentMethod::Post,
        'address' => '1 ถนนหลัก',
    ]);

    packingChecklist()->markPacked($order->number, $staff);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->fresh()->packed_at)->not->toBeNull();
});

test('packing a leftover ready for pickup order only sets packed at', function () {
    $staff = User::factory()->create();
    $order = packableOrder([
        'number' => 'PACKLEFT01',
        'status' => OrderStatus::ReadyForPickup,
    ]);

    packingChecklist()->markPacked($order->number, $staff);

    expect($order->fresh()->status)->toBe(OrderStatus::ReadyForPickup)
        ->and($order->fresh()->packed_at)->not->toBeNull();
});

test('it rejects unmarking a completed pickup order', function () {
    $staff = User::factory()->create();
    $order = packableOrder([
        'number' => 'PACKDONE02',
        'status' => OrderStatus::Completed,
        'packed_at' => now(),
    ]);

    expect(fn () => packingChecklist()->unmarkPacked($order->number, $staff))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->packed_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Completed);
});

test('it rejects unknown not packable and already packed numbers', function () {
    $staff = User::factory()->create();
    packableOrder(['number' => 'PACKALRDY1']);
    packingChecklist()->markPacked('PACKALRDY1', $staff);
    packableOrder(['number' => 'PACKPEND02', 'status' => OrderStatus::PendingReview]);

    expect(fn () => packingChecklist()->markPacked('MISSING01', $staff))->toThrow(ValidationException::class)
        ->and(fn () => packingChecklist()->markPacked('PACKPEND02', $staff))->toThrow(ValidationException::class)
        ->and(fn () => packingChecklist()->markPacked('PACKALRDY1', $staff))->toThrow(ValidationException::class)
        ->and(fn () => packingChecklist()->unmarkPacked('PACKPEND02', $staff))->toThrow(ValidationException::class)
        ->and(fn () => packingChecklist()->unmarkPacked('MISSING01', $staff))->toThrow(ValidationException::class);
});

test('printing does not set packed at', function () {
    $order = packableOrder(['number' => 'PACKPRINT1']);

    packingExporter()->pdf();

    expect($order->fresh()->packed_at)->toBeNull();
});

test('packed today excludes yesterday', function () {
    $staff = User::factory()->create();
    $this->travelTo(now()->subDay());
    $yesterday = packableOrder(['number' => 'PACKYEST01']);
    packingChecklist()->markPacked($yesterday->number, $staff);
    $this->travelBack();

    $today = packableOrder(['number' => 'PACKTODAY1']);
    packingChecklist()->markPacked($today->number, $staff);

    $numbers = packingChecklist()->packedToday()->pluck('number');

    expect($numbers)->toContain($today->number)
        ->and($numbers)->not->toContain($yesterday->number);
});

test('staff can mark and unmark packed from the packing page', function () {
    $staff = User::factory()->create();
    $order = packableOrder([
        'number' => 'PACKLIVE01',
        'full_name' => 'แพ็ค ไลฟ์',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->set('packNumber', '')
        ->call('markPacked')
        ->assertHasErrors('packNumber')
        ->set('packNumber', 'MISSING01')
        ->call('markPacked')
        ->assertHasErrors('packNumber')
        ->set('packNumber', $order->number)
        ->call('markPacked')
        ->assertHasNoErrors()
        ->assertSet('packNumber', '')
        ->assertDispatched('admin-toast', message: 'แพ็คแล้ว')
        ->assertSee('แพ็ค ไลฟ์', false);

    expect($order->fresh()->packed_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::ReadyForPickup)
        ->and(packingChecklist()->orders())->toHaveCount(0);

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->call('unmarkPacked', $order->number)
        ->assertHasNoErrors()
        ->assertDispatched('admin-toast', message: 'ยกเลิกแพ็คแล้ว');

    expect($order->fresh()->packed_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->set('packNumber', $order->number)
        ->call('markPacked')
        ->assertHasNoErrors()
        ->set('packNumber', $order->number)
        ->call('unmarkPacked')
        ->assertHasNoErrors()
        ->assertSet('packNumber', '')
        ->assertDispatched('admin-toast', message: 'ยกเลิกแพ็คแล้ว');

    expect($order->fresh()->packed_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});
