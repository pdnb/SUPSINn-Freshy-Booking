<?php

use App\Models\AdsBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the home page hides the banner slot when none are active', function () {
    AdsBanner::factory()->create(['is_active' => false]);

    Livewire::test('pages::storefront.home')
        ->assertDontSee('data-od-id="home-banner"', false);
});

test('the home page shows active banners that open an external url', function () {
    AdsBanner::factory()->create([
        'is_active' => true,
        'url' => 'https://example.com/campaign',
        'sort_order' => 1,
    ]);

    Livewire::test('pages::storefront.home')
        ->assertSee('data-od-id="home-banner"', false)
        ->assertSee('https://example.com/campaign', false)
        ->assertSee('target="_blank"', false);
});

test('the home page shows banners without a link as plain images', function () {
    AdsBanner::factory()->create([
        'is_active' => true,
        'url' => null,
        'sort_order' => 1,
    ]);

    Livewire::test('pages::storefront.home')
        ->assertSee('data-od-id="home-banner"', false)
        ->assertDontSee('target="_blank"', false);
});

test('the home page carousel hides prev and next buttons when multiple banners exist', function () {
    AdsBanner::factory()->count(2)->create([
        'is_active' => true,
    ]);

    Livewire::test('pages::storefront.home')
        ->assertSee('data-od-id="home-banner"', false)
        ->assertSee('x-on:pointerdown="onPointerDown($event)"', false)
        ->assertDontSee('aria-label="แบนเนอร์ก่อนหน้า"', false)
        ->assertDontSee('aria-label="แบนเนอร์ถัดไป"', false);
});

test('the home page banner slides use opacity transitions', function () {
    AdsBanner::factory()->count(2)->create([
        'is_active' => true,
    ]);

    Livewire::test('pages::storefront.home')
        ->assertSee('x-transition:enter="transition ease-out duration-500"', false)
        ->assertSee('x-transition:enter-start="opacity-0"', false)
        ->assertSee('x-transition:leave-end="opacity-0"', false)
        ->assertSee('aspect-[2/1]', false)
        ->assertSee('absolute inset-0', false);
});
