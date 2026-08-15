<?php

use App\Enums\OrderStatus;
use App\Models\BookingRound;
use App\Models\Order;
use App\Services\Production\ProductionSummaryExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

function productionExporter(): ProductionSummaryExporter
{
    return app(ProductionSummaryExporter::class);
}

function confirmedExportOrder(array $overrides, array $choices): Order
{
    $order = Order::factory()->create(array_merge([
        'status' => OrderStatus::Confirmed,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], $overrides));

    $order->items()->create([
        'name' => 'คอมโบ ปี 70',
        'price' => '1290.00',
        'qty' => 1,
        'choices' => $choices,
    ]);

    return $order;
}

test('it can export csv pdf and excel for the current filters', function () {
    $round = BookingRound::factory()->create(['name' => 'รอบส่งโรงงาน']);

    confirmedExportOrder([
        'booking_round_id' => $round->id,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ], [
        ['label' => 'เสื้อ · ไซส์', 'value' => 'XL'],
    ]);

    $filters = [
        'booking_round_id' => $round->id,
        'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
    ];

    $csv = TestResponse::fromBaseResponse(productionExporter()->csv($filters));
    expect($csv->headers->get('content-type'))->toContain('text/csv')
        ->and($csv->streamedContent())->toContain('สินค้า')
        ->and($csv->streamedContent())->toContain('เสื้อ · ไซส์')
        ->and($csv->streamedContent())->toContain('XL');

    $xlsx = TestResponse::fromBaseResponse(productionExporter()->xlsx($filters));
    expect($xlsx->headers->get('content-type'))->toContain('spreadsheetml');

    $pdf = TestResponse::fromBaseResponse(productionExporter()->pdf($filters));
    expect($pdf->headers->get('content-disposition'))->toContain('.pdf')
        ->and(substr($pdf->getContent(), 0, 4))->toBe('%PDF');
});
