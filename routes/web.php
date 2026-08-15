<?php

use App\Http\Controllers\Admin\OrderSlipController;
use App\Http\Controllers\Admin\ProductionExportController;
use App\Http\Controllers\GuestOrderSlipController;
use App\Http\Controllers\LineSessionController;
use App\Http\Middleware\HardenOrderTracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::storefront.home')->name('home');
Route::livewire('/products/{product:slug}', 'pages::storefront.product-show')->name('products.show');
Route::livewire('/cart', 'pages::storefront.cart')->name('cart');
Route::livewire('/checkout', 'pages::storefront.checkout')->name('checkout');
Route::livewire('/pay', 'pages::storefront.payment')->name('pay');
Route::livewire('/orders', 'pages::storefront.order-track')->name('orders.index');
Route::get('/orders/{order}/slip', GuestOrderSlipController::class)
    ->middleware(['throttle:order-tracking', HardenOrderTracking::class])
    ->name('orders.slip');
Route::livewire('/orders/{order}/{token}', 'pages::storefront.order-confirmation')
    ->middleware(['throttle:order-tracking', HardenOrderTracking::class])
    ->name('orders.confirmation');
Route::post('/line/session', LineSessionController::class)
    ->middleware('throttle:line-session')
    ->name('line.session');

Route::middleware('guest')->group(function () {
    Route::livewire('/admin/login', 'pages::admin.login')->name('login');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/orders', 'pages::admin.order-queue')->name('orders');
    Route::get('/orders/{order}/slip', OrderSlipController::class)->name('orders.slip');
    Route::livewire('/orders/{order}', 'pages::admin.order-detail')->name('orders.show');
    Route::livewire('/fulfillment', 'pages::admin.fulfillment')->name('fulfillment');
    Route::livewire('/pickup', 'pages::admin.pickup')->name('pickup');
    Route::livewire('/products', 'pages::admin.product-index')->name('products');
    Route::livewire('/products/create', 'pages::admin.product-edit')->name('products.create');
    Route::livewire('/products/{product}/edit', 'pages::admin.product-edit')->name('products.edit');
    Route::livewire('/rounds', 'pages::admin.rounds')->name('rounds');
    Route::get('/production/export/{format}', ProductionExportController::class)->name('production.export');
    Route::livewire('/production', 'pages::admin.production')->name('production');
    Route::livewire('/inventory', 'pages::admin.inventory')->name('inventory');
    Route::livewire('/settings', 'pages::admin.settings')->name('settings');
});
