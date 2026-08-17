<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PaymentMode;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\DepositSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function payPageReady(): void
{
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
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);
}

test('the payment page shows the amount and promptpay details', function () {
    payPageReady();

    Livewire::test('pages::storefront.payment')
        ->assertSee('350.00')
        ->assertSee('PromptPay')
        ->assertSee('images/Thai_QR_Logo.svg', false)
        ->assertSee('data:image/svg+xml', false)
        ->assertSee('ยืนยันการจอง');
});

test('a guest can confirm a booking with a passing slip', function () {
    Storage::fake('local');
    payPageReady();

    Livewire::test('pages::storefront.payment')
        ->set('slip', UploadedFile::fake()->image('slip.jpg'))
        ->call('confirm')
        ->assertRedirect();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->slip)->not->toBeNull();
});

test('a failing slip stays on payment and creates no order', function () {
    Storage::fake('local');
    payPageReady();

    Livewire::test('pages::storefront.payment')
        ->set('slip', UploadedFile::fake()->image('fail-slip.jpg'))
        ->call('confirm')
        ->assertHasErrors('slip');

    expect(Order::query()->count())->toBe(0);
});

test('the payment page shows the deposit amount due now', function () {
    $shirt = app(CatalogService::class)->create([
        'name' => 'เสื้อ ปี 69',
        'type' => 'simple',
        'price' => '350',
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ]);
    openBookingRound([$shirt]);
    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']], 3);
    app(DepositSettingService::class)->update('500');
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
        'payment_mode' => PaymentMode::Deposit->value,
    ]);

    Livewire::test('pages::storefront.payment')
        ->assertSee('500.00')
        ->assertSee('คงเหลือตอนรับ')
        ->assertSee('550.00');
});
