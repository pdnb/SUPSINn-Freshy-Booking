<?php

use App\Enums\ProductType;
use App\Models\BookingRound;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\CatalogService;
use App\Services\Catalog\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function catalogService(): CatalogService
{
    return app(CatalogService::class);
}

function catalogShirt(array $overrides = []): Product
{
    return catalogService()->create(array_merge([
        'name' => 'เสื้อ ปี 70',
        'type' => ProductType::Simple->value,
        'price' => '350',
        'is_active' => true,
        'option_groups' => [
            ['key' => 'size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L']],
        ],
    ], $overrides));
}

test('admin list filters products by name type and active status', function () {
    catalogShirt(['name' => 'เสื้อ ปี 70']);
    catalogService()->create([
        'name' => 'เข็มที่ระลึก',
        'type' => ProductType::Simple->value,
        'price' => '50',
        'is_active' => false,
        'option_groups' => [],
    ]);
    catalogService()->create([
        'name' => 'คอมโบเซ็ต ปี 70',
        'type' => ProductType::Bundle->value,
        'price' => '1290',
        'components' => [
            [
                'name' => 'ชุดนักศึกษา',
                'option_groups' => [
                    ['key' => 'gender', 'label' => 'เพศ', 'values' => ['ชาย', 'หญิง']],
                ],
            ],
        ],
    ]);

    expect(catalogService()->adminList(['search' => 'เสื้อ'])->pluck('name')->all())
        ->toBe(['เสื้อ ปี 70'])
        ->and(catalogService()->adminList(['type' => ProductType::Bundle->value])->pluck('name')->all())
        ->toBe(['คอมโบเซ็ต ปี 70'])
        ->and(catalogService()->adminList(['is_active' => false])->pluck('name')->all())
        ->toBe(['เข็มที่ระลึก'])
        ->and(catalogService()->adminPaginate(['search' => 'เสื้อ'], 1)->pluck('name')->all())
        ->toBe(['เสื้อ ปี 70']);
});

test('admin list can filter by booking round', function () {
    $shirt = catalogShirt();
    $pin = catalogService()->create([
        'name' => 'เข็มที่ระลึก',
        'type' => ProductType::Simple->value,
        'price' => '50',
        'option_groups' => [],
    ]);
    $round = BookingRound::factory()->create(['name' => 'รอบปี 70']);
    $round->products()->attach($shirt);

    expect(catalogService()->adminList(['booking_round_id' => $round->id])->pluck('id')->all())
        ->toBe([$shirt->id])
        ->and(catalogService()->adminList()->pluck('id')->all())
        ->toContain($shirt->id, $pin->id);
});

test('duplicate copies composition as an inactive product with a copy suffix', function () {
    $source = catalogService()->create([
        'name' => 'SRU คอมโบเซ็ต ปี 70',
        'description' => 'ชุดรวม',
        'type' => ProductType::Bundle->value,
        'price' => '1290',
        'option_groups' => [],
        'components' => [
            [
                'name' => 'ชุดนักศึกษา',
                'option_groups' => [
                    ['key' => 'gender', 'label' => 'เพศ / แบบเสื้อ', 'values' => ['ชาย', 'หญิง']],
                ],
            ],
        ],
    ]);

    $copy = catalogService()->duplicate($source);

    expect($copy->id)->not->toBe($source->id)
        ->and($copy->name)->toBe('SRU คอมโบเซ็ต ปี 70 (สำเนา)')
        ->and($copy->is_active)->toBeFalse()
        ->and($copy->type)->toBe(ProductType::Bundle)
        ->and($copy->price)->toBe('1290.00')
        ->and($copy->description)->toBe('ชุดรวม')
        ->and($copy->components)->toHaveCount(1)
        ->and($copy->components->first()->name)->toBe('ชุดนักศึกษา')
        ->and($copy->components->first()->optionGroups->first()->key)->toBe('gender')
        ->and($copy->components->first()->optionGroups->first()->values->pluck('value')->all())
        ->toBe(['ชาย', 'หญิง'])
        ->and($source->fresh()->name)->toBe('SRU คอมโบเซ็ต ปี 70')
        ->and($source->fresh()->is_active)->toBeTrue();
});

test('duplicate copies image files to new paths so deleting one does not remove the other', function () {
    Storage::fake('public');
    Storage::disk('public')->put('product-images/original.jpg', 'original-bytes');

    $source = catalogShirt();
    ProductImage::factory()->for($source)->create([
        'path' => 'product-images/original.jpg',
        'sort_order' => 1,
    ]);

    $copy = catalogService()->duplicate($source->fresh('images'));
    $copiedImage = $copy->images->first();

    expect($copy->images)->toHaveCount(1)
        ->and($copiedImage->path)->not->toBe('product-images/original.jpg')
        ->and($copiedImage->sort_order)->toBe(1);

    Storage::disk('public')->assertExists($copiedImage->path);
    Storage::disk('public')->assertExists('product-images/original.jpg');

    app(ProductImageService::class)->deleteImage($copiedImage);

    Storage::disk('public')->assertMissing($copiedImage->path);
    Storage::disk('public')->assertExists('product-images/original.jpg');
});

test('clone into round copies selected products onto the destination without changing the source', function () {
    $sourceRound = BookingRound::factory()->create(['name' => 'รอบปี 70']);
    $destinationRound = BookingRound::factory()->create(['name' => 'รอบปี 71']);
    $shirt = catalogShirt();
    $pin = catalogService()->create([
        'name' => 'เข็มที่ระลึก',
        'type' => ProductType::Simple->value,
        'price' => '50',
        'option_groups' => [],
    ]);
    $sourceRound->products()->attach([$shirt->id, $pin->id]);

    $copies = catalogService()->cloneIntoRound($sourceRound, $destinationRound, [$shirt->id]);

    expect($copies)->toHaveCount(1)
        ->and($copies->first()->name)->toBe('เสื้อ ปี 70 (สำเนา)')
        ->and($copies->first()->is_active)->toBeFalse()
        ->and($copies->first()->optionGroups->first()->values->pluck('value')->all())->toBe(['S', 'M', 'L'])
        ->and($destinationRound->fresh('products')->products->pluck('id')->all())
        ->toBe([$copies->first()->id])
        ->and($sourceRound->fresh('products')->products->pluck('id')->all())
        ->toEqualCanonicalizing([$shirt->id, $pin->id])
        ->and($shirt->fresh()->name)->toBe('เสื้อ ปี 70');
});

test('clone into round rejects the same round or products that are not on the source', function () {
    $round = BookingRound::factory()->create();
    $other = BookingRound::factory()->create();
    $shirt = catalogShirt();
    $round->products()->attach($shirt);

    expect(fn () => catalogService()->cloneIntoRound($round, $round, [$shirt->id]))
        ->toThrow(ValidationException::class);

    expect(fn () => catalogService()->cloneIntoRound($round, $other, [$shirt->id + 999]))
        ->toThrow(ValidationException::class);
});
