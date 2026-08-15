<?php

use App\Enums\OrderStatus;
use App\Models\BookingRound;
use App\Models\Order;
use App\Services\Production\ProductionSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function productionSummary(): ProductionSummaryService
{
    return app(ProductionSummaryService::class);
}

function orderWithChoices(array $overrides, array $choices, int $qty = 1, string $name = 'คอมโบ ปี 70'): Order
{
    $order = Order::factory()->create($overrides);

    $order->items()->create([
        'name' => $name,
        'price' => '1290.00',
        'qty' => $qty,
        'choices' => $choices,
    ]);

    return $order;
}

test('it counts confirmed and later statuses by product choice and quantity', function () {
    $round = BookingRound::factory()->create(['name' => 'รอบปี 70']);

    orderWithChoices([
        'status' => OrderStatus::Confirmed,
        'booking_round_id' => $round->id,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], [
        ['label' => 'เสื้อ · ไซส์', 'value' => 'M'],
        ['label' => 'กางเกง · ไซส์', 'value' => 'L'],
    ], 2);

    orderWithChoices([
        'status' => OrderStatus::ReadyForPickup,
        'booking_round_id' => $round->id,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], [
        ['label' => 'เสื้อ · ไซส์', 'value' => 'M'],
    ]);

    orderWithChoices([
        'status' => OrderStatus::PendingReview,
        'booking_round_id' => $round->id,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], [
        ['label' => 'เสื้อ · ไซส์', 'value' => 'M'],
    ]);

    orderWithChoices([
        'status' => OrderStatus::Cancelled,
        'booking_round_id' => $round->id,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], [
        ['label' => 'เสื้อ · ไซส์', 'value' => 'XL'],
    ]);

    $rows = productionSummary()->summarize(['booking_round_id' => $round->id]);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['product_name'])->toBe('คอมโบ ปี 70')
        ->and($rows[0]['choice_label'])->toBe('กางเกง · ไซส์')
        ->and($rows[0]['choice_value'])->toBe('L')
        ->and($rows[0]['qty'])->toBe(2)
        ->and($rows[1]['choice_label'])->toBe('เสื้อ · ไซส์')
        ->and($rows[1]['choice_value'])->toBe('M')
        ->and($rows[1]['qty'])->toBe(3);
});

test('it can filter the summary by faculty', function () {
    $round = BookingRound::factory()->create();

    orderWithChoices([
        'status' => OrderStatus::Confirmed,
        'booking_round_id' => $round->id,
        'faculty' => 'คณะครุศาสตร์',
    ], [
        ['label' => 'ไซส์เสื้อ', 'value' => 'S'],
    ], 1, 'เสื้อ ปี 69');

    orderWithChoices([
        'status' => OrderStatus::Shipped,
        'booking_round_id' => $round->id,
        'faculty' => 'คณะพยาบาลศาสตร์',
    ], [
        ['label' => 'ไซส์เสื้อ', 'value' => 'S'],
    ], 4, 'เสื้อ ปี 69');

    $rows = productionSummary()->summarize([
        'booking_round_id' => $round->id,
        'faculty' => 'คณะพยาบาลศาสตร์',
    ]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['qty'])->toBe(4);
});

test('it does not mix quantities from another booking round', function () {
    $roundA = BookingRound::factory()->create(['name' => 'รอบ A']);
    $roundB = BookingRound::factory()->create(['name' => 'รอบ B']);

    orderWithChoices([
        'status' => OrderStatus::Completed,
        'booking_round_id' => $roundA->id,
    ], [
        ['label' => 'ไซส์เสื้อ', 'value' => 'M'],
    ], 1, 'เสื้อ ปี 69');

    orderWithChoices([
        'status' => OrderStatus::Completed,
        'booking_round_id' => $roundB->id,
    ], [
        ['label' => 'ไซส์เสื้อ', 'value' => 'M'],
    ], 9, 'เสื้อ ปี 69');

    $rows = productionSummary()->summarize(['booking_round_id' => $roundA->id]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['qty'])->toBe(1);
});
