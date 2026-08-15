<?php

use App\Models\AdsBanner;
use App\Services\Ads\AdsBannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adsBanners(): AdsBannerService
{
    return app(AdsBannerService::class);
}

test('it creates a banner with image and url', function () {
    Storage::fake('public');

    $banner = adsBanners()->create([
        'url' => 'https://example.com/freshy',
        'image' => UploadedFile::fake()->image('promo.jpg', 800, 400),
        'is_active' => true,
    ]);

    expect($banner->url)->toBe('https://example.com/freshy')
        ->and($banner->is_active)->toBeTrue();

    Storage::disk('public')->assertExists($banner->image_path);
});

test('it creates a banner without a url', function () {
    Storage::fake('public');

    $banner = adsBanners()->create([
        'url' => '',
        'image' => UploadedFile::fake()->image('promo.jpg', 800, 400),
        'is_active' => true,
    ]);

    expect($banner->url)->toBeNull();
});

test('it can toggle and reorder banners', function () {
    Storage::fake('public');

    $first = AdsBanner::factory()->create(['sort_order' => 1, 'is_active' => true, 'url' => 'https://example.com/a']);
    $second = AdsBanner::factory()->create(['sort_order' => 2, 'is_active' => true, 'url' => 'https://example.com/b']);

    adsBanners()->setActive($first, false);
    adsBanners()->move($second, -1);

    expect($first->fresh()->is_active)->toBeFalse()
        ->and($second->fresh()->sort_order)->toBe(1)
        ->and($first->fresh()->sort_order)->toBe(2);
});

test('active storefront banners are ordered and skip inactive ones', function () {
    AdsBanner::factory()->create(['sort_order' => 2, 'is_active' => true, 'url' => 'https://example.com/second']);
    AdsBanner::factory()->create(['sort_order' => 1, 'is_active' => true, 'url' => 'https://example.com/first']);
    AdsBanner::factory()->create(['sort_order' => 0, 'is_active' => false, 'url' => 'https://example.com/hidden']);

    $active = adsBanners()->activeForStorefront();

    expect($active)->toHaveCount(2)
        ->and($active->pluck('url')->all())->toBe([
            'https://example.com/first',
            'https://example.com/second',
        ]);
});
