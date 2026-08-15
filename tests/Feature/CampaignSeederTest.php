<?php

use App\Enums\ProductType;
use App\Models\BookingRound;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\Booking\BookingRoundService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('db seed prepares a bookable 69/70 campaign', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', config('booking.admin_email'))->exists())->toBeTrue()
        ->and(Product::query()->count())->toBe(3)
        ->and(ShippingRate::query()->where('is_active', true)->count())->toBe(2);

    $shirt = Product::query()->where('slug', 'freshy-69-shirt')->first();
    $pants = Product::query()->where('slug', 'freshy-69-pants')->first();
    $combo = Product::query()->where('slug', 'sru-combo-70')->first();

    expect($shirt)->not->toBeNull()
        ->and($shirt->type)->toBe(ProductType::Simple)
        ->and($shirt->optionGroups)->toHaveCount(1)
        ->and($pants)->not->toBeNull()
        ->and($pants->type)->toBe(ProductType::Simple)
        ->and($combo)->not->toBeNull()
        ->and($combo->type)->toBe(ProductType::Bundle)
        ->and($combo->components)->toHaveCount(4)
        ->and($combo->components->pluck('name')->all())
        ->toBe(['ชุดนักศึกษา', 'ชุดเฟรชชี่', 'เสื้อกิจกรรม', 'เครื่องหมาย']);

    $round = BookingRound::query()->where('name', 'รอบเปิดจองชุดเฟรชชี่')->first();

    expect($round)->not->toBeNull()
        ->and($round->isOpenAt())->toBeTrue()
        ->and($round->products->pluck('id')->all())
        ->toEqualCanonicalizing([$shirt->id, $pants->id, $combo->id]);

    expect(app(BookingRoundService::class)->storefrontProducts())
        ->toHaveCount(3);
});

test('the storefront lists seeded campaign products after db seed', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSee('ชุดเฟรชชี่ ปี 69 — เสื้อ', false)
        ->assertSee('ชุดเฟรชชี่ ปี 69 — กางเกง', false)
        ->assertSee('SRU คอมโบเซ็ต ปี 70', false)
        ->assertDontSee('ยังไม่เปิดรับจอง', false);

    Livewire::test('pages::storefront.home')
        ->assertSee('ชุดเฟรชชี่ ปี 69 — เสื้อ')
        ->assertSee('SRU คอมโบเซ็ต ปี 70');
});

test('a guest can add a seeded year 69 shirt to the cart', function () {
    $this->seed(DatabaseSeeder::class);

    $shirt = Product::query()->where('slug', 'freshy-69-shirt')->firstOrFail();

    Livewire::test('pages::storefront.product-show', ['product' => $shirt])
        ->call('selectOption', 'size', 'M')
        ->call('addToCart')
        ->assertRedirect(route('cart'));

    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('ชุดเฟรชชี่ ปี 69 — เสื้อ', false);
});

test('storefront pages keep a skip link and a main landmark', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSee('ข้ามไปเนื้อหา', false)
        ->assertSee('id="content"', false);

    $shirt = Product::query()->where('slug', 'freshy-69-shirt')->firstOrFail();

    $this->get(route('products.show', $shirt))
        ->assertOk()
        ->assertSee('ข้ามไปเนื้อหา', false)
        ->assertSee('id="content"', false);
});

test('seeding the campaign twice does not duplicate catalog data', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Product::query()->count())->toBe(3)
        ->and(BookingRound::query()->where('name', 'รอบเปิดจองชุดเฟรชชี่')->count())->toBe(1)
        ->and(ShippingRate::query()->count())->toBe(2)
        ->and(User::query()->where('email', config('booking.admin_email'))->count())->toBe(1);
});
