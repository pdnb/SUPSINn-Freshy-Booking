<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Order\OrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function seededShirt(): Product
{
    return Product::query()->where('slug', 'freshy-69-shirt')->firstOrFail();
}

function fillSeededCheckout(Product $shirt, string $fulfillment = 'bookstore'): void
{
    app(CartService::class)->add($shirt->fresh(['optionGroups.values']), ['options' => ['size' => 'M']]);
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => $fulfillment,
        'address_line' => $fulfillment === FulfillmentMethod::Post->value ? '123 ถนนสุขุมวิท' : null,
        'subdistrict' => $fulfillment === FulfillmentMethod::Post->value ? 'คลองเตย' : null,
        'district' => $fulfillment === FulfillmentMethod::Post->value ? 'คลองเตย' : null,
        'province' => $fulfillment === FulfillmentMethod::Post->value ? 'กรุงเทพฯ' : null,
        'postcode' => $fulfillment === FulfillmentMethod::Post->value ? '10110' : null,
    ]);
}

test('srs 8: seeded catalog round and shipping are ready for a guest demo', function () {
    $shirt = seededShirt();

    $this->get('/')
        ->assertOk()
        ->assertSee('ชุดเฟรชชี่ ปี 69 — เสื้อ', false)
        ->assertSee('SRU คอมโบเซ็ต ปี 70', false)
        ->assertDontSee('เข้าสู่ระบบ', false);

    $this->get(route('products.show', $shirt))->assertOk();

    expect(ShippingRate::query()->where('is_active', true)->count())->toBe(2);

    app(CartService::class)->add($shirt, ['options' => ['size' => 'M']]);

    Livewire::test('pages::storefront.checkout')
        ->assertSee(FulfillmentMethod::Bookstore->label())
        ->assertSee(FulfillmentMethod::Hall->label())
        ->assertSee(FulfillmentMethod::Post->label())
        ->set('fulfillment', FulfillmentMethod::Post->value)
        ->assertDontSee('เรทค่าส่ง', false)
        ->assertSee('ระบบคำนวณให้อัตโนมัติ', false)
        ->assertSee('50.00')
        ->assertSee('400.00');
});

test('srs 8: checksum failure blocks the order', function () {
    Storage::fake('local');

    fillSeededCheckout(seededShirt());

    Livewire::test('pages::storefront.payment')
        ->set('slip', UploadedFile::fake()->image('fail-slip.jpg'))
        ->call('confirm')
        ->assertHasErrors('slip');

    expect(Order::query()->count())->toBe(0);
});

test('srs 8: a passing slip can be placed twice without locking stock', function () {
    Storage::fake('local');
    $shirt = seededShirt();
    $orders = app(OrderService::class);

    fillSeededCheckout($shirt);
    $first = $orders->place(UploadedFile::fake()->image('pass-a.jpg', 80, 80));

    expect($first->status)->toBe(OrderStatus::PendingReview)
        ->and($first->tracking_token)->toHaveLength(40);

    $this->get(route('orders.confirmation', [
        'order' => $first,
        'token' => $first->tracking_token,
    ]))
        ->assertOk()
        ->assertSee($first->number, false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    fillSeededCheckout($shirt);
    $orders->place(UploadedFile::fake()->image('pass-b.jpg', 160, 120));

    expect(Order::query()->count())->toBe(2);
});

test('srs 8: a seeded admin can confirm a pending review order', function () {
    Storage::fake('local');
    $shirt = seededShirt();
    fillSeededCheckout($shirt);

    Livewire::test('pages::storefront.payment')
        ->set('slip', UploadedFile::fake()->image('queue-slip.jpg'))
        ->call('confirm');

    $order = Order::query()->firstOrFail();
    $admin = User::query()->where('email', config('booking.admin_email'))->firstOrFail();

    expect($order->status)->toBe(OrderStatus::PendingReview)
        ->and($order->student_id)->toBe('67011234567');

    app(OrderService::class)->transition($order, OrderStatus::Confirmed, $admin);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});
