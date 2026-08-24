<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests cannot visit the admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect(route('login'));
});

test('guests can view the admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('เข้าสู่ระบบ', false)
        ->assertSee('SRU Shop', false)
        ->assertSee('กลับหน้าร้าน', false);
});

test('the storefront stays public', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('SRU Shop', false);
});

test('invalid credentials do not authenticate', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
    ]);

    Livewire::test('pages::admin.login')
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email')
        ->assertNoRedirect();

    $this->assertGuest();
});

test('the admin console uses a gray and white palette', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)
        ->toContain('--bg: oklch(97% 0 0)')
        ->toContain('--surface: oklch(100% 0 0)')
        ->toContain('--accent: oklch(28% 0 0)')
        ->not->toContain('--accent: oklch(58% 0.16 145)');
});

test('staff can log in and reach the admin dashboard', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
    ]);

    Livewire::test('pages::admin.login')
        ->set('email', 'admin@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('ภาพรวม', false)
        ->assertDontSee('คิวสลิปและจัดส่งน้ำหนักเท่ากัน', false)
        ->assertSee('ดูออเดอร์', false)
        ->assertDontSeeHtml('>คิวสลิป</span>')
        ->assertDontSee('เข้าคิวสลิป', false)
        ->assertSee('คิวรอตรวจ', false)
        ->assertSee('รายการค้าง', false)
        ->assertDontSee('ต้องสนใจ', false)
        ->assertDontSee('งานวันเปิดจอง', false)
        ->assertSee('Anuphan', false)
        ->assertSee('ข้ามไปเนื้อหาหลัก', false)
        ->assertSee('ออกจากระบบ', false);
});

test('staff can log out and lose admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();

    $this->get('/admin')
        ->assertRedirect(route('login'));
});

test('login is rate limited after too many failures', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
    ]);

    $login = Livewire::test('pages::admin.login')
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password');

    foreach (range(1, 5) as $attempt) {
        $login->call('authenticate')->assertHasErrors('email');
    }

    $login->call('authenticate')->assertHasErrors('email');

    expect($login->errors()->first('email'))->toContain('ลองใหม่');
});
