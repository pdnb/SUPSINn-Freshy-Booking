<?php

use App\Services\Catalog\CatalogService;
use App\Services\Storefront\StorefrontLogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('header v2 preview shows the remapped chrome without a location row', function () {
    Livewire::test('pages::storefront.header-v2-preview')
        ->assertSee('SRU Freshy Shop', false)
        ->assertSee('ค้นหาสินค้า...', false)
        ->assertSeeHtml('aria-label="หน้าหลัก"')
        ->assertSeeHtml('images/subsinn-logo.png')
        ->assertSeeHtml('role="search"')
        ->assertSeeHtml('href="'.route('home').'"')
        ->assertSeeHtml('href="'.route('cart').'"')
        ->assertDontSeeHtml('role="listbox"')
        ->assertDontSee('Delivery Location')
        ->assertDontSee('จุดรับของ')
        ->assertDontSee('Al Wasl');
});

test('header v2 left button falls back to a home icon after the logo is cleared', function () {
    app(StorefrontLogoService::class)->clear();

    Livewire::test('pages::storefront.header-v2-preview')
        ->assertSeeHtml('aria-label="หน้าหลัก"')
        ->assertDontSeeHtml('images/subsinn-logo.png');
});

test('the header v2 preview route is reachable', function () {
    $this->get(route('preview.header-v2'))
        ->assertSuccessful()
        ->assertSee('ตัวอย่าง header เวอร์ชันใหม่', false);
});

test('the home page uses header v2', function () {
    Livewire::test('pages::storefront.home')
        ->assertSeeHtml('images/subsinn-logo.png')
        ->assertSee('ค้นหาสินค้า...', false)
        ->assertSeeHtml('aria-label="หน้าหลัก"')
        ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
        ->assertDontSeeHtml('<x-storefront.input')
        ->assertDontSeeHtml('role="listbox"')
        ->assertDontSee('ไม่พบสินค้า');
});

test('home search dropdown lists matching open products and keeps the grid intact', function () {
    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
    ]);
    $pants = app(CatalogService::class)->create([
        'name' => 'กางเกง ปี 69',
        'type' => 'simple',
        'price' => '350',
    ]);
    app(CatalogService::class)->create([
        'name' => 'เสื้อนอกรอบ',
        'type' => 'simple',
        'price' => '199',
    ]);

    openBookingRound([$shirt, $pants]);

    $page = Livewire::test('pages::storefront.home')
        ->set('search', 'เสื้อ')
        ->assertSeeHtml('role="listbox"')
        ->assertSeeHtml('href="'.route('products.show', $shirt).'"')
        ->assertDontSee('เสื้อนอกรอบ')
        ->assertSee('กางเกง ปี 69', false);

    expect($page->html())->toContain('สินค้าเปิดจอง');
});

test('home search dropdown shows an empty state for unknown names', function () {
    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
    ]);

    openBookingRound([$shirt]);

    Livewire::test('pages::storefront.home')
        ->set('search', 'ไม่มีสินค้านี้อยู่')
        ->assertSee('ไม่พบสินค้า', false)
        ->assertDontSeeHtml('role="listbox"')
        ->assertSee('เสื้อ ปี 69', false);
});
