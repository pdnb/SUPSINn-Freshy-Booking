<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('storefront home tabbar and cart button include heroicons', function () {
    $response = $this->get('/')
        ->assertOk()
        ->assertSee('aria-label="เมนูหลัก"', false)
        ->assertSee('aria-label="ตะกร้า', false);

    $html = $response->getContent();

    expect(substr_count($html, '<svg'))->toBeGreaterThanOrEqual(4);
});

test('empty cart and empty order pages show icons', function () {
    $cart = $this->get(route('cart'))
        ->assertOk()
        ->assertSee('ยังไม่มีสินค้าในตะกร้า', false)
        ->getContent();

    expect($cart)->toContain('<svg');

    $orders = $this->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ยังไม่มีคำสั่งซื้อ', false)
        ->getContent();

    expect($orders)->toContain('<svg');
});

test('icon-only cart and back controls keep aria-labels', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('aria-label="ตะกร้า', false);

    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('aria-label="กลับหน้าหลัก"', false)
        ->assertSee('<svg', false);
});

test('admin dashboard shows a logout icon', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('ออกจากระบบ', false)
        ->getContent();

    expect($html)->toContain('<svg');
});

test('admin button icons match sidebar icon size', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.btn svg\s*\{[^}]*width:\s*16px/s');
});

test('admin login page shows lock icon beside title', function () {
    $html = $this->get('/admin/login')
        ->assertOk()
        ->assertSee('เข้าสู่ระบบแอดมิน', false)
        ->assertSee('มรส. ชุดเฟรชชี่', false)
        ->assertSee('ข้ามไปแบบฟอร์มเข้าสู่ระบบ', false)
        ->getContent();

    expect($html)->toContain('<svg');
});
