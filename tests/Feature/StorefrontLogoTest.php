<?php

use App\Models\User;
use App\Services\Storefront\StorefrontLogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('storefront header shows the default logo image', function () {
    Livewire::test('pages::storefront.home')
        ->assertSeeHtml('images/subsinn-logo.png')
        ->assertSeeHtml('aria-label="หน้าหลัก"');
});

test('storefront header shows a home icon after the logo is cleared', function () {
    app(StorefrontLogoService::class)->clear();

    Livewire::test('pages::storefront.home')
        ->assertSeeHtml('aria-label="หน้าหลัก"')
        ->assertDontSeeHtml('images/subsinn-logo.png');
});

test('settings logo tab uses a single-file dropzone', function () {
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'logo')
        ->assertSee('โลโก้', false)
        ->assertSeeHtml('id="logo-image"')
        ->assertSee('ลากโลโก้มาวางที่นี่', false)
        ->assertSeeHtml('class="dropzone"');
});

test('staff can upload and clear the storefront logo from settings', function () {
    Storage::fake('public');
    $staff = User::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'logo')
        ->set('logo_image', UploadedFile::fake()->image('brand.png', 200, 80))
        ->call('saveLogo')
        ->assertHasNoErrors();

    $path = app(StorefrontLogoService::class)->path();
    expect($path)->toStartWith('logos/');
    Storage::disk('public')->assertExists($path);

    Livewire::actingAs($staff)
        ->test('pages::admin.settings')
        ->set('tab', 'logo')
        ->call('openClearLogo')
        ->assertSet('showLogoClearConfirm', true)
        ->assertSee('ต้องการลบโลโก้หรือไม่', false)
        ->call('confirmClearLogo')
        ->assertSet('showLogoClearConfirm', false);

    expect(app(StorefrontLogoService::class)->url())->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
