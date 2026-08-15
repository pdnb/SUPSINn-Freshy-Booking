<?php

use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function cart(): CartService
{
    return app(CartService::class);
}

function simpleShirt(): Product
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

function comboBundle(): Product
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

test('it adds a simple product with required options when the round is open', function () {
    $shirt = simpleShirt();
    openBookingRound([$shirt]);

    $item = cart()->add($shirt, ['options' => ['size' => 'M']]);

    expect($item['product_id'])->toBe($shirt->id)
        ->and($item['qty'])->toBe(1)
        ->and(cart()->count())->toBe(1)
        ->and(cart()->items())->toHaveCount(1);
});

test('it rejects a bundle when a component option is missing', function () {
    $combo = comboBundle();
    openBookingRound([$combo]);

    cart()->add($combo, [
        'components' => [
            $combo->components->first()->id => ['size' => 'M'],
        ],
    ]);
})->throws(ValidationException::class);

test('it adds a bundle when every component option is selected', function () {
    $combo = comboBundle();
    openBookingRound([$combo]);

    $item = cart()->add($combo, [
        'components' => [
            $combo->components[0]->id => ['size' => 'M'],
            $combo->components[1]->id => ['size' => 'S'],
        ],
    ]);

    expect($item['product_id'])->toBe($combo->id)
        ->and(cart()->count())->toBe(1);
});

test('it rejects a product that is not in an open round', function () {
    $shirt = simpleShirt();

    cart()->add($shirt, ['options' => ['size' => 'M']]);
})->throws(ValidationException::class);

test('it rejects adding to the cart when no booking round is open', function () {
    $shirt = simpleShirt();
    openBookingRound([$shirt], [
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
    ]);

    cart()->add($shirt, ['options' => ['size' => 'M']]);
})->throws(ValidationException::class);

test('it updates quantity and removes a line', function () {
    $shirt = simpleShirt();
    openBookingRound([$shirt]);

    $item = cart()->add($shirt, ['options' => ['size' => 'M']]);
    cart()->updateQty($item['id'], 3);

    expect(cart()->count())->toBe(3)
        ->and(cart()->subtotal())->toBe('1050.00');

    cart()->remove($item['id']);

    expect(cart()->items())->toHaveCount(0)
        ->and(cart()->subtotal())->toBe('0.00');
});
