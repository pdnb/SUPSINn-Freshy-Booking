<?php

use App\Models\AdsBanner;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the storefront layout shows a page loading overlay and navigates chrome links in-place', function () {
    $html = $this->get('/')
        ->assertOk()
        ->assertSee('กำลังโหลด...', false)
        ->assertSee('page-loading-overlay', false)
        ->assertSee('aria-label="เมนูหลัก"', false)
        ->getContent();

    expect($html)->toContain('wire:navigate')
        ->and($html)->not->toContain('href="#content" wire:navigate');
});

test('product cards use wire:navigate and external banners do not', function () {
    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);

    openBookingRound([$shirt]);

    AdsBanner::factory()->create([
        'is_active' => true,
        'url' => 'https://example.com/campaign',
        'sort_order' => 1,
    ]);

    $html = Livewire::test('pages::storefront.home')
        ->assertSee($shirt->name, false)
        ->assertSee('https://example.com/campaign', false)
        ->html();

    expect($html)->toContain('wire:navigate')
        ->and($html)->toContain(route('products.show', $shirt))
        ->and($html)->not->toMatch('/<a[^>]*href="https:\/\/example\.com\/campaign"[^>]*wire:navigate/')
        ->and($html)->not->toMatch('/<a[^>]*wire:navigate[^>]*href="https:\/\/example\.com\/campaign"/');
});

test('the cart checkout call to action uses wire:navigate', function () {
    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);

    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);

    Livewire::test('pages::storefront.cart')
        ->assertSee('ดำเนินการจอง', false)
        ->assertSee(route('checkout'), false)
        ->assertSeeHtml('wire:navigate');
});
