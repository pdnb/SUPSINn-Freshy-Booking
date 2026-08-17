<?php

use App\Enums\ProductType;
use App\Models\AdsBanner;
use App\Models\BookingRound;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('staff can open catalog rounds production and settings pages', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.products'))
        ->assertOk()
        ->assertSee('สินค้า', false)
        ->assertSee('aria-label="ค้นหา"', false)
        ->assertSee('aria-label="ชนิด"', false)
        ->assertSee('aria-label="สถานะ"', false)
        ->assertSee('เปิดขาย', false)
        ->assertSee('ปิดขาย', false)
        ->assertSee('ล้างตัวกรอง', false)
        ->assertDontSeeHtml('<label class="field">');

    $this->actingAs($staff)
        ->get(route('admin.rounds'))
        ->assertOk()
        ->assertSee('รอบจอง', false);

    $production = $this->actingAs($staff)
        ->get(route('admin.production'))
        ->assertOk()
        ->assertSee('สรุปยอดผลิต', false)
        ->assertSee('นับจำนวนต่อสินค้าจากออเดอร์ที่ยืนยันแล้ว', false)
        ->assertSee('aria-label="รอบจอง"', false)
        ->assertSee('aria-label="คณะ"', false)
        ->assertSee('ล้างตัวกรอง', false)
        ->assertDontSeeHtml('<label class="field">')
        ->assertSee('CSV', false)
        ->assertSee('Excel', false)
        ->assertSee('PDF', false)
        ->getContent();

    expect(substr_count($production, 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'))
        ->toBe(3)
        ->and($production)->toContain('row-tight');

    $css = file_get_contents(resource_path('css/admin.css'));
    expect($css)->toMatch('/\.row-tight\s*\{[^}]*gap:\s*var\(--gap-xs\)/s');

    $this->actingAs($staff)
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('เรทค่าส่ง', false);
});

test('staff can create a product from the admin editor', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit')
        ->set('name', 'เข็มที่ระลึก')
        ->set('price', '50')
        ->set('type', 'simple')
        ->set('optionGroups', [])
        ->call('publish')
        ->assertRedirect(route('admin.products'));

    $product = Product::query()->where('name', 'เข็มที่ระลึก')->first();

    expect($product)->not->toBeNull()
        ->and($product->is_active)->toBeTrue();
});

test('product editor shows image validation messages and does not create the product', function () {
    Storage::fake('public');
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit')
        ->set('name', 'เข็มมีรูปไม่ถูกต้อง')
        ->set('price', '50')
        ->set('type', 'simple')
        ->set('optionGroups', [])
        ->set('uploads', [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')])
        ->call('publish')
        ->assertHasErrors('images.0')
        ->assertSee('ไฟล์ที่อัปโหลดต้องเป็นรูปภาพ', false);

    expect(Product::query()->where('name', 'เข็มมีรูปไม่ถูกต้อง')->exists())->toBeFalse();
});

test('staff can save a product as draft from the admin editor', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit')
        ->set('name', 'เข็มฉบับร่าง')
        ->set('price', '30')
        ->set('type', 'simple')
        ->set('optionGroups', [])
        ->call('saveDraft')
        ->assertRedirect(route('admin.products'));

    expect(Product::query()->where('name', 'เข็มฉบับร่าง')->first()?->is_active)->toBeFalse();
});

test('product index filters by type and sale status', function () {
    $staff = User::factory()->create();
    $simpleOn = Product::factory()->create([
        'name' => 'เสื้อเปิดขาย',
        'type' => ProductType::Simple,
        'is_active' => true,
    ]);
    $bundleOff = Product::factory()->create([
        'name' => 'ชุดปิดขาย',
        'type' => ProductType::Bundle,
        'is_active' => false,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.product-index')
        ->assertSee($simpleOn->name, false)
        ->assertSee($bundleOff->name, false)
        ->set('type', 'simple')
        ->assertSee($simpleOn->name, false)
        ->assertDontSee($bundleOff->name)
        ->set('type', 'all')
        ->set('status', 'active')
        ->assertSee($simpleOn->name, false)
        ->assertDontSee($bundleOff->name)
        ->set('status', 'draft')
        ->assertSee($bundleOff->name, false)
        ->assertDontSee($simpleOn->name)
        ->call('clearFilters')
        ->assertSet('type', 'all')
        ->assertSet('status', 'all')
        ->assertSee($simpleOn->name, false)
        ->assertSee($bundleOff->name, false);
});

test('production page can clear filters', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.production')
        ->set('booking_round_id', '1')
        ->set('faculty', 'คณะวิศวกรรมศาสตร์')
        ->call('clearFilters')
        ->assertSet('booking_round_id', '')
        ->assertSet('faculty', '');
});

test('product editor uses the ecommerce-admin grid layout and type radio cards', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit')
        ->assertSeeHtml('class="grid-2-1"')
        ->assertSeeHtml('class="radio-card"')
        ->assertSeeHtml('role="radiogroup"')
        ->assertSee('ข้อมูลพื้นฐาน', false)
        ->assertSee('ราคาและตัวเลือก', false)
        ->assertSee('รูปภาพ', false)
        ->assertSeeHtml('class="dropzone"')
        ->assertSee('ลากรูปมาวางที่นี่', false)
        ->assertSee('บันทึกฉบับร่าง', false)
        ->assertSee('เผยแพร่', false)
        ->assertDontSeeHtml('<select class="select" wire:model.live="type"')
        ->set('type', 'bundle')
        ->assertSet('type', 'bundle');

    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toContain('.radio-card')
        ->and($css)->toContain('.grid-2-1')
        ->and($css)->toContain('.dropzone');
});

test('product editor keeps option group fields on one row and can add component groups', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit')
        ->set('type', 'bundle')
        ->call('addComponent')
        ->assertSee('เพิ่มสินค้า', false)
        ->assertSee('ชื่อสินค้า', false)
        ->assertSee('ลบสินค้า', false)
        ->assertSee('เพิ่มตัวเลือก', false)
        ->assertSee('Label', false)
        ->assertSee('Values', false)
        ->assertDontSeeHtml('>คีย์</label>')
        ->assertSeeHtml('class="tag-input"')
        ->assertSeeHtml('aria-label="ลบกลุ่ม"')
        ->call('addComponentOptionGroup', 0)
        ->assertCount('components.0.option_groups', 2)
        ->call('pushComponentOptionGroupValues', 0, 1, 'แดง, น้ำเงิน')
        ->assertSet('components.0.option_groups.1.values', ['แดง', 'น้ำเงิน'])
        ->call('removeComponentOptionGroupValue', 0, 1, 0)
        ->assertSet('components.0.option_groups.1.values', ['น้ำเงิน'])
        ->call('askRemoveComponentOptionGroup', 0, 1)
        ->assertSet('showDeleteConfirm', true)
        ->call('closeDeleteConfirm')
        ->assertSet('showDeleteConfirm', false)
        ->assertCount('components.0.option_groups', 2)
        ->call('askRemoveComponentOptionGroup', 0, 1)
        ->call('confirmDelete')
        ->assertCount('components.0.option_groups', 1)
        ->call('askRemoveComponent', 0)
        ->assertSee('ต้องการลบ', false)
        ->call('confirmDelete')
        ->assertCount('components', 0);

    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.field-row\.option-group-row\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)\s+minmax\(0,\s*1\.4fr\)\s+auto/s')
        ->and($css)->toContain('.field-row.option-group-row > .icon-btn')
        ->and($css)->toContain('.tag-input');
});

test('product editor auto-generates option group keys on save', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit')
        ->set('name', 'ชุดคอมโบทดสอบ')
        ->set('price', '900')
        ->set('type', 'bundle')
        ->set('components', [
            [
                'name' => 'ชุดนักศึกษา',
                'option_groups' => [
                    ['key' => '', 'label' => 'เพศ', 'values' => ['ชาย', 'หญิง']],
                    ['key' => '', 'label' => 'ไซส์', 'values' => ['S', 'M', 'L']],
                ],
            ],
        ])
        ->call('publish')
        ->assertRedirect(route('admin.products'));

    $product = Product::query()->where('name', 'ชุดคอมโบทดสอบ')->with('components.optionGroups')->first();

    expect($product)->not->toBeNull();

    $keys = $product->components->first()->optionGroups->pluck('key')->all();

    expect($keys)->toHaveCount(2)
        ->and($keys[0])->not->toBe('')
        ->and($keys[1])->not->toBe('')
        ->and($keys[0])->not->toBe($keys[1]);
});

test('staff can create a booking round', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.rounds')
        ->call('create')
        ->set('name', 'รอบเปิดจองทดสอบ')
        ->set('starts_at', now()->subHour()->format('Y-m-d\TH:i'))
        ->set('ends_at', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('is_enabled', true)
        ->call('save')
        ->assertSee('รอบเปิดจองทดสอบ', false);
});

test('staff can edit a booking round from the list', function () {
    $staff = User::factory()->create();
    $round = BookingRound::factory()->create([
        'name' => 'รอบเดิม',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
        'is_enabled' => true,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.rounds')
        ->assertSee('แก้ไข', false)
        ->call('edit', $round->id)
        ->assertSet('editingId', $round->id)
        ->assertSet('name', 'รอบเดิม')
        ->assertSee('แก้ไขรอบ', false)
        ->set('name', 'รอบใหม่')
        ->call('save')
        ->assertSet('editingId', null)
        ->assertSee('รอบใหม่', false);

    expect($round->fresh()->name)->toBe('รอบใหม่');
});

test('staff can create a shipping rate from settings', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('rate_name', 'ไปรษณีย์มาตรฐาน')
        ->set('tiers', [
            ['min_qty' => '1', 'max_qty' => '2', 'amount' => '50'],
            ['min_qty' => '3', 'max_qty' => '', 'amount' => '80'],
        ])
        ->call('saveRate')
        ->assertSee('ไปรษณีย์มาตรฐาน', false);
});

test('staff can edit a shipping rate from settings', function () {
    $staff = User::factory()->create();
    $rate = ShippingRate::factory()->create([
        'name' => 'เรทเดิม',
        'tiers' => [
            ['min_qty' => 1, 'max_qty' => null, 'amount' => '40.00'],
        ],
        'amount' => '40.00',
        'is_active' => true,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->assertSee('แก้ไข', false)
        ->call('editRate', $rate->id)
        ->assertSet('rateId', $rate->id)
        ->assertSet('rate_name', 'เรทเดิม')
        ->assertSee('แก้ไขเรท', false)
        ->set('rate_name', 'เรทใหม่')
        ->set('tiers', [
            ['min_qty' => '1', 'max_qty' => '', 'amount' => '55'],
        ])
        ->call('saveRate')
        ->assertSet('rateId', null)
        ->assertSee('เรทใหม่', false);

    expect($rate->fresh()->name)->toBe('เรทใหม่');
});

test('settings banner form uses a single-file dropzone', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'banners')
        ->assertSeeHtml('class="dropzone"')
        ->assertSeeHtml('id="banner-image"')
        ->assertSee('ลากแบนเนอร์มาวางที่นี่', false)
        ->assertDontSeeHtml('<input class="input" type="file" wire:model="banner_image"');
});

test('staff must confirm before deleting a banner', function () {
    $staff = User::factory()->create();
    $banner = AdsBanner::factory()->create([
        'url' => 'https://example.com/promo',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'banners')
        ->call('openDeleteBanner', $banner->id)
        ->assertSet('showBannerDeleteConfirm', true)
        ->assertSet('bannerPendingDeleteId', $banner->id)
        ->assertSee('ต้องการลบแบนเนอร์นี้หรือไม่', false)
        ->call('closeDeleteBanner')
        ->assertSet('showBannerDeleteConfirm', false);

    expect($banner->fresh())->not->toBeNull();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'banners')
        ->call('openDeleteBanner', $banner->id)
        ->call('confirmDeleteBanner')
        ->assertSet('showBannerDeleteConfirm', false);

    expect(AdsBanner::query()->find($banner->id))->toBeNull();
});

test('settings banner reorder buttons use icons', function () {
    $staff = User::factory()->create();
    AdsBanner::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'banners')
        ->assertSeeHtml('aria-label="เลื่อนขึ้น"')
        ->assertSeeHtml('aria-label="เลื่อนลง"')
        ->assertDontSeeHtml('>ขึ้น</button>')
        ->assertDontSeeHtml('>ลง</button>');
});

test('settings shipping form uses a switch for active state', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->assertSeeHtml('class="switch"')
        ->assertSeeHtml('class="switch-input"')
        ->assertSee('เปิดใช้', false)
        ->assertDontSeeHtml('<input type="checkbox" class="check" wire:model="rate_active"')
        ->set('rate_active', false)
        ->assertSet('rate_active', false);

    expect(file_get_contents(resource_path('css/admin.css')))->toContain('.switch-track');
});

test('settings shipping tiers stay on one row', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->assertSeeHtml('class="field-row tier-row"');

    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.field-row\.tier-row\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)\s+minmax\(0,\s*1fr\)\s+minmax\(0,\s*1fr\)\s+auto/s');
});
