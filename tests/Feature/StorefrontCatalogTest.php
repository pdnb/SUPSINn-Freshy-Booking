<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function storefrontShirt(): Product
{
    return app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);
}

function storefrontCombo(): Product
{
    return app(CatalogService::class)->create([
        'name' => 'คอมโบ ปี 70',
        'type' => 'bundle',
        'price' => '1290',
        'components' => [
            [
                'name' => 'เสื้อ',
                'option_groups' => [
                    ['key' => 'size', 'label' => 'ไซส์', 'values' => ['S', 'M']],
                ],
            ],
            [
                'name' => 'กางเกง',
                'option_groups' => [
                    ['key' => 'size', 'label' => 'ไซส์', 'values' => ['S', 'M']],
                ],
            ],
        ],
    ]);
}

test('the home page explains that booking is closed when no round is open', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('ยังไม่เปิดรับจอง', false)
        ->assertDontSee('เพิ่มตะกร้า', false);
});

test('the home page shows the short booking steps when a round is open', function () {
    openBookingRound([storefrontShirt()]);

    Livewire::test('pages::storefront.home')
        ->assertSee('ขั้นตอนสั้น ๆ', false)
        ->assertSee('เลือกสินค้าและไซส์', false)
        ->assertSee('กรอกรายละเอียดนักศึกษา', false)
        ->assertSee('เลือกรับเองหรือไปรษณีย์', false)
        ->assertSee('สแกนชำระเงินและแนบสลิป', false)
        ->assertSee('aria-labelledby="home-steps-heading"', false);
});

test('the home page lists only products in an open booking round', function () {
    $onSale = storefrontShirt();
    $notInRound = app(CatalogService::class)->create([
        'name' => 'กางเกงนอกรอบ',
        'type' => 'simple',
        'price' => '299',
    ]);

    openBookingRound([$onSale]);

    Livewire::test('pages::storefront.home')
        ->assertSee('เสื้อ ปี 69')
        ->assertSee('overflow-hidden rounded-brand border', false)
        ->assertDontSee('กางเกงนอกรอบ');

    $this->get(route('products.show', $notInRound))->assertNotFound();
});

test('an inactive product in an open round is hidden from the storefront', function () {
    $hidden = storefrontShirt();
    $hidden->update(['name' => 'เสื้อปิดขาย', 'is_active' => false]);
    openBookingRound([$hidden]);

    Livewire::test('pages::storefront.home')
        ->assertDontSee('เสื้อปิดขาย')
        ->assertDontSee('ยังไม่เปิดรับจอง');
});

test('the home page shows the cover image when a product has images', function () {
    Storage::fake('public');

    $withImage = storefrontShirt();
    $withoutImage = storefrontCombo();
    ProductImage::factory()->for($withImage)->create(['sort_order' => 1]);

    openBookingRound([$withImage, $withoutImage]);

    Livewire::test('pages::storefront.home')
        ->assertSee($withImage->coverImage->url(), false)
        ->assertSee('alt="'.$withImage->name.'"', false)
        ->assertSee('ชุด', false);
});

test('the product page shows a gallery in sort order or the placeholder', function () {
    Storage::fake('public');

    $shirt = storefrontShirt();
    ProductImage::factory()->for($shirt)->create(['sort_order' => 2]);
    ProductImage::factory()->for($shirt)->create(['sort_order' => 1]);

    openBookingRound([$shirt]);

    $urls = $shirt->images()->get()->map(fn (ProductImage $image) => $image->url())->all();

    Livewire::test('pages::storefront.product-show', ['product' => $shirt])
        ->assertSeeInOrder($urls, false)
        ->assertSee('aria-label="รูปถัดไป"', false);

    $plain = storefrontCombo();
    openBookingRound([$plain]);

    Livewire::test('pages::storefront.product-show', ['product' => $plain])
        ->assertSee('ชุด', false)
        ->assertDontSee('aria-label="รูปถัดไป"', false);
});

test('a guest can add a simple product from the product page', function () {
    $shirt = storefrontShirt();
    openBookingRound([$shirt]);

    Livewire::test('pages::storefront.product-show', ['product' => $shirt])
        ->assertSeeHtml("wire:target=\"selectOption('size', 'M')\"")
        ->assertSeeHtml('wire:target="addToCart"')
        ->assertSeeHtml('wire:loading.attr="disabled"')
        ->call('selectOption', 'size', 'M')
        ->call('addToCart')
        ->assertRedirect(route('cart'));

    Livewire::test('pages::storefront.cart')
        ->assertSee('เสื้อ ปี 69')
        ->assertSee('ไซส์เสื้อ')
        ->assertSee('M');
});

test('a bundle cannot be added until every component is configured', function () {
    $combo = storefrontCombo();
    openBookingRound([$combo]);

    Livewire::test('pages::storefront.product-show', ['product' => $combo])
        ->assertSeeHtml("wire:target=\"selectComponentOption({$combo->components[0]->id}, 'size', 'M')\"")
        ->call('selectComponentOption', $combo->components[0]->id, 'size', 'M')
        ->call('addToCart')
        ->assertHasNoErrors('components')
        ->assertDispatched('storefront-toast', message: 'กรุณาเลือกตัวเลือกให้ครบทุกชิ้น');
});
