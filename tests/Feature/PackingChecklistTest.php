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

    $html = view('admin.packing-checklist.pdf', [
        'orders' => packingChecklist()->orders(),
        'roundName' => 'ทุกรอบ',
        'faculty' => 'ทุกคณะ',
        'channelLabel' => 'ทุกช่องทาง',
        'fontPath' => str_replace('\\', '/', resource_path('fonts/Sarabun-Regular.ttf')),
    ])->render();

    expect($html)->toContain('PACKPDF001')
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
        ->assertSee('aria-label="รอบจอง"', false)
        ->assertSee('aria-label="ช่องทาง"', false)
        ->assertSee('aria-label="คณะ"', false)
        ->assertSee('ล้างตัวกรอง', false)
        ->assertDontSeeHtml('<label class="field">')
        ->assertSee('PACKPAGE01', false)
        ->assertSee('นภา ทดสอบ', false)
        ->assertSee('PDF', false);

    Livewire::actingAs($staff)
        ->test('pages::admin.packing-checklist')
        ->set('booking_round_id', '1')
        ->set('fulfillment', 'post')
        ->set('faculty', 'คณะวิศวกรรมศาสตร์')
        ->call('clearFilters')
        ->assertSet('booking_round_id', '')
        ->assertSet('fulfillment', '')
        ->assertSet('faculty', '');
});
