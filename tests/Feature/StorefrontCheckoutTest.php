<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PaymentMode;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\DepositSettingService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function pageShirt(): Product
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

test('staff guests can change quantity and open checkout from the cart', function () {
    $shirt = pageShirt();
    openBookingRound([$shirt]);
    $item = app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);

    Livewire::test('pages::storefront.cart')
        ->assertSeeHtml('wire:loading.attr="disabled"')
        ->assertSeeHtml("wire:target=\"increment('{$item['id']}')\"")
        ->assertSeeHtml("wire:target=\"decrement('{$item['id']}')\"")
        ->assertSeeHtml("wire:target=\"remove('{$item['id']}')\"")
        ->call('increment', $item['id'])
        ->call('increment', $item['id'])
        ->assertSee('1,050.00')
        ->assertSee('ดำเนินการจอง');
});

test('checkout pickup shows zero shipping and saves a draft', function () {
    $shirt = pageShirt();
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);
    app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    Livewire::test('pages::storefront.checkout')
        ->set('student_id', '67011234567')
        ->set('full_name', 'สมชาย ใจดี')
        ->set('faculty', 'คณะวิทยาศาสตร์และเทคโนโลยี')
        ->set('major', 'วิทยาการคอมพิวเตอร์')
        ->set('phone', '0812345678')
        ->set('fulfillment', FulfillmentMethod::Bookstore->value)
        ->assertSee('0.00')
        ->assertSeeHtml('wire:click="save"')
        ->assertSeeHtml('wire:target="save"')
        ->assertSeeHtml('wire:loading.attr="disabled"')
        ->call('save')
        ->assertRedirect(route('pay'));
});

test('checkout postage adds shipping to the payable total', function () {
    $shirt = pageShirt();
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);
    app(ShippingRateService::class)->create([
        'name' => 'ทั่วประเทศ',
        'amount' => '50',
        'is_active' => true,
    ]);

    Livewire::test('pages::storefront.checkout')
        ->set('student_id', '67011234567')
        ->set('full_name', 'สมชาย ใจดี')
        ->set('faculty', 'คณะวิทยาศาสตร์และเทคโนโลยี')
        ->set('major', 'วิทยาการคอมพิวเตอร์')
        ->set('phone', '0812345678')
        ->set('fulfillment', FulfillmentMethod::Post->value)
        ->set('address_line', '123 ถนนสุขุมวิท')
        ->set('subdistrict', 'คลองเตย')
        ->set('district', 'คลองเตย')
        ->set('province', 'กรุงเทพฯ')
        ->set('postcode', '10110')
        ->assertDontSee('เรทค่าส่ง', false)
        ->assertSee('400.00')
        ->call('save')
        ->assertRedirect(route('pay'));
});

test('checkout offers deposit for pickup when the cart exceeds the deposit amount', function () {
    $shirt = pageShirt();
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']], 3);
    app(DepositSettingService::class)->update('500');

    Livewire::test('pages::storefront.checkout')
        ->set('fulfillment', FulfillmentMethod::Bookstore->value)
        ->assertSee('การชำระเงิน')
        ->assertSee('มัดจำ')
        ->set('payment_mode', PaymentMode::Deposit->value)
        ->assertSee('500.00')
        ->assertSee('550.00');
});
