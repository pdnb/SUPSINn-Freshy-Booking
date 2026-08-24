<?php

use App\Contracts\SlipVerifier;
use App\Enums\SlipVerificationResult;
use App\Services\Ads\AdsBannerService;
use App\Services\Booking\BookingRoundService;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderService;
use App\Services\Payment\SlipVerificationService;
use App\Services\Payment\StubSlipVerifier;
use App\Services\Production\ProductionSummaryService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the home page renders the branded storefront', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('SRU Shop', false)
        ->assertSee('Anuphan', false);
});

test('the home page is a livewire component', function () {
    Livewire::test('pages::storefront.home')
        ->assertOk()
        ->assertSee('SRU Shop');
});

test('the slip verifier contract is bound to the stub', function () {
    expect(app(SlipVerifier::class))->toBeInstanceOf(StubSlipVerifier::class);
    expect(app(SlipVerifier::class)->verify('slip.jpg'))->toBe(SlipVerificationResult::Pass);
});

test('application service classes exist for each domain area', function () {
    expect(app(CatalogService::class))->toBeInstanceOf(CatalogService::class);
    expect(app(BookingRoundService::class))->toBeInstanceOf(BookingRoundService::class);
    expect(app(ShippingRateService::class))->toBeInstanceOf(ShippingRateService::class);
    expect(app(CartService::class))->toBeInstanceOf(CartService::class);
    expect(app(CheckoutService::class))->toBeInstanceOf(CheckoutService::class);
    expect(app(OrderService::class))->toBeInstanceOf(OrderService::class);
    expect(app(SlipVerificationService::class))->toBeInstanceOf(SlipVerificationService::class);
    expect(app(ProductionSummaryService::class))->toBeInstanceOf(ProductionSummaryService::class);
    expect(app(AdsBannerService::class))->toBeInstanceOf(AdsBannerService::class);
    expect(app(InventoryService::class))->toBeInstanceOf(InventoryService::class);
});
