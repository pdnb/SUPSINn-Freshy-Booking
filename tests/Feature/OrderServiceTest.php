<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Models\Order;
use App\Models\PaymentSlip;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\DepositSettingService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function orders(): OrderService
{
    return app(OrderService::class);
}

function paymentShirt(): Product
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

function readyToPay(): Product
{
    $shirt = paymentShirt();
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

    return $shirt;
}

test('a passing slip creates a pending review order without locking stock', function () {
    Storage::fake('local');
    $shirt = readyToPay();

    $order = orders()->place(UploadedFile::fake()->image('slip.jpg'));

    expect($order->status)->toBe(OrderStatus::PendingReview)
        ->and($order->slip)->not->toBeNull()
        ->and($order->items)->toHaveCount(1)
        ->and($order->payment_mode)->toBe(PaymentMode::Full)
        ->and($order->amount_due_now)->toBe('350.00')
        ->and($order->amount_remaining)->toBe('0.00')
        ->and($shirt->fresh()->is_active)->toBeTrue()
        ->and(app(CartService::class)->items())->toHaveCount(0)
        ->and(session('order.tracking'))->toMatchArray([
            'number' => $order->number,
            'token' => $order->tracking_token,
        ]);

    Storage::disk('local')->assertExists($order->slip->path);
});

test('placing with deposit stores due and remaining amounts', function () {
    Storage::fake('local');
    $shirt = paymentShirt();
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

    $order = orders()->place(UploadedFile::fake()->image('deposit-slip.jpg'));

    expect($order->payment_mode)->toBe(PaymentMode::Deposit)
        ->and($order->total)->toBe('1050.00')
        ->and($order->amount_due_now)->toBe('500.00')
        ->and($order->amount_remaining)->toBe('550.00')
        ->and($order->balance_collected_at)->toBeNull();
});

test('a failing slip is rejected and no order is created', function () {
    Storage::fake('local');
    readyToPay();

    expect(fn () => orders()->place(UploadedFile::fake()->image('fail-slip.jpg')))
        ->toThrow(ValidationException::class);

    expect(Order::query()->count())->toBe(0)
        ->and(PaymentSlip::query()->count())->toBe(0);
});

test('a duplicate slip from the stub is rejected', function () {
    Storage::fake('local');
    readyToPay();

    expect(fn () => orders()->place(UploadedFile::fake()->image('dup-slip.jpg')))
        ->toThrow(ValidationException::class);

    expect(Order::query()->count())->toBe(0);
});

test('the same slip checksum cannot complete a second order', function () {
    Storage::fake('local');
    $shirt = readyToPay();

    $bytes = random_bytes(128);
    orders()->place(UploadedFile::fake()->createWithContent('pass-a.jpg', $bytes));

    app(CartService::class)->add($shirt, ['options' => ['size' => 'L']]);
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);

    expect(fn () => orders()->place(UploadedFile::fake()->createWithContent('pass-b.jpg', $bytes)))
        ->toThrow(ValidationException::class);

    expect(Order::query()->count())->toBe(1);
});
