<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the admin layout always includes the toast host', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-admin-toast', false)
        ->assertSee('toast-host', false)
        ->assertDontSee('บันทึกปีการศึกษาแล้ว', false);
});

test('the admin toast host seeds from a flashed status on full page load', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)
        ->withSession(['status' => 'เผยแพร่สินค้าแล้ว'])
        ->get(route('admin.products'))
        ->assertOk()
        ->assertSee('data-admin-toast', false)
        ->assertSee('เผยแพร่สินค้าแล้ว', false);
});
