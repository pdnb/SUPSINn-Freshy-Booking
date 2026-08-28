<?php

use App\Enums\InventoryAdjustmentReason;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests cannot visit inventory', function () {
    $this->get(route('admin.inventory'))
        ->assertRedirect(route('login'));
});

test('staff can adjust on-hand stock from the inventory page', function () {
    $staff = User::factory()->create();
    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['M']],
        ],
    ]);

    Livewire::actingAs($staff)
        ->test('pages::admin.inventory')
        ->assertSee('เสื้อ ปี 69', false)
        ->assertSee('เคลียร์', false)
        ->set('search', 'ไม่มีสินค้านี้')
        ->set('stock', 'low')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('stock', '')
        ->assertSee('เสื้อ ปี 69', false)
        ->call('openAdjust', $shirt->id, 'ไซส์เสื้อ', 'M', $shirt->name)
        ->set('delta', '7')
        ->set('reason', InventoryAdjustmentReason::FactoryReceipt->value)
        ->call('applyAdjust')
        ->assertSee('7', false)
        ->assertDispatched('admin-toast', message: 'ปรับยอดของที่มีแล้ว');

    expect(InventoryItem::query()->where('product_id', $shirt->id)->value('on_hand'))->toBe(7);
});
