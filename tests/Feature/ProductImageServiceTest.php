<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('add images stores files on the public disk and continues sort order', function () {
    Storage::fake('public');
    Storage::disk('public')->put('product-images/existing.jpg', 'fake');

    $product = Product::factory()->create();
    ProductImage::factory()->for($product)->create(['path' => 'product-images/existing.jpg', 'sort_order' => 2]);

    app(ProductImageService::class)->addImages($product, [
        UploadedFile::fake()->image('front.jpg'),
        UploadedFile::fake()->image('back.jpg'),
    ]);

    $images = $product->images()->get();

    expect($images)->toHaveCount(3)
        ->and($images->pluck('sort_order')->all())->toBe([2, 3, 4]);

    foreach ($images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

test('delete image removes the row and the file from disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('product-images/dead.jpg', 'fake');

    $image = ProductImage::factory()->create(['path' => 'product-images/dead.jpg']);

    app(ProductImageService::class)->deleteImage($image);

    expect(ProductImage::query()->find($image->id))->toBeNull();
    Storage::disk('public')->assertMissing('product-images/dead.jpg');
});

test('move swaps sort order with the neighbour of the same product only', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();

    $first = ProductImage::factory()->for($product)->create(['sort_order' => 1]);
    $second = ProductImage::factory()->for($product)->create(['sort_order' => 2]);
    $outsider = ProductImage::factory()->for($other)->create(['sort_order' => 0]);

    $service = app(ProductImageService::class);
    $service->move($second, -1);

    expect($second->fresh()->sort_order)->toBe(1)
        ->and($first->fresh()->sort_order)->toBe(2)
        ->and($outsider->fresh()->sort_order)->toBe(0);

    $service->move($second->fresh(), -1);

    expect($second->fresh()->sort_order)->toBe(1);
});

test('set as cover moves the image ahead of the current first image', function () {
    $product = Product::factory()->create();
    $first = ProductImage::factory()->for($product)->create(['sort_order' => 1]);
    $cover = ProductImage::factory()->for($product)->create(['sort_order' => 3]);

    app(ProductImageService::class)->setAsCover($cover);

    expect($product->fresh()->coverImage->id)->toBe($cover->id)
        ->and($first->fresh()->sort_order)->toBe(1)
        ->and($cover->fresh()->sort_order)->toBe(0);
});

test('the cover image is the first image by sort order', function () {
    $product = Product::factory()->create();
    ProductImage::factory()->for($product)->create(['sort_order' => 5]);
    $cover = ProductImage::factory()->for($product)->create(['sort_order' => 1]);

    expect($product->coverImage->id)->toBe($cover->id);
});
