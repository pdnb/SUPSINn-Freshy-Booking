<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

test('status pills do not stretch inside flex stacks', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.pill\s*\{[^}]*align-self:\s*flex-start/s')
        ->and($css)->toMatch('/\.pill\s*\{[^}]*width:\s*fit-content/s');
});

test('semantic pill colors keep chroma while chrome accent stays gray', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)
        ->toMatch('/--accent:\s*oklch\([^)]*\s0\s0\)/')
        ->toMatch('/--success:\s*oklch\(\d+% 0\.\d+/')
        ->toMatch('/--info:\s*oklch\(\d+% 0\.\d+/')
        ->toMatch('/\.pill-paid[^{]*\{[^}]*var\(--success-soft\)/s');
});

test('table headers do not use mono font', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    expect($css)->toMatch('/\.ds-table th\s*\{[^}]*font-size:\s*12\.5px/s')
        ->and($css)->not->toMatch('/\.ds-table th\s*\{[^}]*font-family:\s*var\(--font-mono\)/s');
});

test('order status pills map to semantic classes', function (OrderStatus $status, string $class) {
    expect($status->pillClass())->toBe($class);

    $html = Blade::render('<x-admin.status-pill :status="$status" />', ['status' => $status]);

    expect($html)->toContain($class);
})->with([
    'pending review' => [OrderStatus::PendingReview, 'pill-pending'],
    'need reslip' => [OrderStatus::NeedReslip, 'pill-danger'],
    'confirmed' => [OrderStatus::Confirmed, 'pill-paid'],
    'ready for pickup' => [OrderStatus::ReadyForPickup, 'pill-paid'],
    'shipped' => [OrderStatus::Shipped, 'pill-info'],
    'completed' => [OrderStatus::Completed, 'pill-info'],
    'cancelled' => [OrderStatus::Cancelled, 'pill-neutral'],
]);

test('completed orders use the info pill style', function () {
    $staff = User::factory()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Completed,
        'number' => 'FRDONE001',
    ]);

    $this->actingAs($staff)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('รับของแล้ว', false)
        ->assertSee('pill-info', false)
        ->assertDontSee('pill-paid', false);
});
