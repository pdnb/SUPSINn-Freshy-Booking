<?php

use App\Models\Setting;
use App\Services\Storefront\StorefrontLogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function storefrontLogo(): StorefrontLogoService
{
    return app(StorefrontLogoService::class);
}

test('missing setting falls back to the bundled default logo', function () {
    expect(storefrontLogo()->path())->toBe(config('booking.default_storefront_logo'))
        ->and(storefrontLogo()->url())->toBe(asset('images/subsinn-logo.png'));
});

test('it stores an uploaded logo on the public disk', function () {
    Storage::fake('public');

    storefrontLogo()->update(UploadedFile::fake()->image('logo.png', 200, 80));

    $path = storefrontLogo()->path();

    expect($path)->toStartWith('logos/')
        ->and(storefrontLogo()->url())->toBe(Storage::disk('public')->url($path));

    Storage::disk('public')->assertExists($path);
});

test('uploading replaces a previous stored logo file', function () {
    Storage::fake('public');

    storefrontLogo()->update(UploadedFile::fake()->image('first.png', 200, 80));
    $firstPath = storefrontLogo()->path();

    storefrontLogo()->update(UploadedFile::fake()->image('second.png', 200, 80));
    $secondPath = storefrontLogo()->path();

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('clear removes a stored logo and falls back to text', function () {
    Storage::fake('public');

    storefrontLogo()->update(UploadedFile::fake()->image('logo.png', 200, 80));
    $path = storefrontLogo()->path();

    storefrontLogo()->clear();

    expect(storefrontLogo()->path())->toBeNull()
        ->and(storefrontLogo()->url())->toBeNull();

    Storage::disk('public')->assertMissing($path);
});

test('clear does not delete the bundled default image path', function () {
    Setting::query()->create([
        'key' => StorefrontLogoService::KEY,
        'value' => config('booking.default_storefront_logo'),
    ]);

    storefrontLogo()->clear();

    expect(storefrontLogo()->path())->toBeNull()
        ->and(file_exists(public_path(config('booking.default_storefront_logo'))))->toBeTrue();
});

test('update requires an image', function () {
    storefrontLogo()->update(null);
})->throws(ValidationException::class);
