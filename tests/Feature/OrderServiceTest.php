<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\SlipVerificationResult;
use App\Models\Order;
use App\Models\PaymentSlip;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\DepositSettingService;
use App\Services\Order\AcademicYearSettingService;
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
        ->and($order->number)->toMatch('/^FB-\d{2}-\d{4}$/')
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
            'auto_open' => true,
        ]);

    Storage::disk('local')->assertExists($order->slip->path);
});

test('placed orders get sequential academic year numbers', function () {
    Storage::fake('local');
    app(AcademicYearSettingService::class)->update('2569');
    $shirt = readyToPay();

    $first = orders()->place(UploadedFile::fake()->createWithContent('slip-a.jpg', random_bytes(128)));

    app(CartService::class)->add($shirt, ['options' => ['size' => 'L']]);
    app(CheckoutService::class)->save([
        'student_id' => '67011234567',
        'full_name' => 'สมชาย ใจดี',
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
        'major' => 'วิทยาการคอมพิวเตอร์',
        'phone' => '0812345678',
        'fulfillment' => FulfillmentMethod::Bookstore->value,
    ]);

    $second = orders()->place(UploadedFile::fake()->createWithContent('slip-b.jpg', random_bytes(128)));

    expect($first->number)->toBe('FB-69-0001')
        ->and($second->number)->toBe('FB-69-0002');
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

function needReslipOrder(array $attributes = []): Order
{
    $order = Order::factory()->create(array_merge([
        'status' => OrderStatus::NeedReslip,
    ], $attributes));

    $path = 'slips/'.$order->id.'/old-slip.jpg';
    Storage::disk('local')->put($path, 'old slip bytes');

    $order->slip()->create([
        'path' => $path,
        'original_name' => 'old-slip.jpg',
        'checksum' => hash('sha256', 'old slip bytes'),
        'verifier_result' => SlipVerificationResult::Pass,
    ]);

    return $order->fresh(['slip']);
}

test('replace slip moves a need reslip order back to pending review', function () {
    Storage::fake('local');
    $order = needReslipOrder();
    $oldPath = $order->slip->path;

    $updated = orders()->replaceSlip($order, UploadedFile::fake()->createWithContent('new-slip.jpg', random_bytes(128)));

    expect($updated->status)->toBe(OrderStatus::PendingReview)
        ->and($updated->slip)->not->toBeNull()
        ->and($updated->slip->original_name)->toBe('new-slip.jpg')
        ->and(PaymentSlip::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and($updated->statusChanges)->toHaveCount(1)
        ->and($updated->statusChanges->first()->from_status)->toBe(OrderStatus::NeedReslip)
        ->and($updated->statusChanges->first()->to_status)->toBe(OrderStatus::PendingReview)
        ->and($updated->statusChanges->first()->user_id)->toBeNull();

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($updated->slip->path);
});

test('a failing reslip keeps the order on need reslip and the old slip', function () {
    Storage::fake('local');
    $order = needReslipOrder();
    $oldPath = $order->slip->path;

    expect(fn () => orders()->replaceSlip($order, UploadedFile::fake()->image('fail-slip.jpg')))
        ->toThrow(ValidationException::class);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::NeedReslip)
        ->and($order->slip->path)->toBe($oldPath);

    Storage::disk('local')->assertExists($oldPath);
});

test('replace slip is rejected when the order is not awaiting a new slip', function () {
    Storage::fake('local');
    $order = Order::factory()->create(['status' => OrderStatus::PendingReview]);

    expect(fn () => orders()->replaceSlip($order, UploadedFile::fake()->image('slip.jpg')))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::PendingReview);
});

test('a reslip cannot reuse another orders slip checksum', function () {
    Storage::fake('local');
    $bytes = random_bytes(128);
    readyToPay();
    orders()->place(UploadedFile::fake()->createWithContent('pass-a.jpg', $bytes));

    $order = needReslipOrder();

    expect(fn () => orders()->replaceSlip($order, UploadedFile::fake()->createWithContent('pass-b.jpg', $bytes)))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::NeedReslip);
});
