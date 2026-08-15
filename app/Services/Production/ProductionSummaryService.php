<?php

namespace App\Services\Production;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Checkout\CheckoutService;
use Illuminate\Support\Collection;

class ProductionSummaryService
{
    public function __construct(private CheckoutService $checkout) {}

    /**
     * @return list<OrderStatus>
     */
    public function countedStatuses(): array
    {
        return [
            OrderStatus::Confirmed,
            OrderStatus::ReadyForPickup,
            OrderStatus::Shipped,
            OrderStatus::Completed,
        ];
    }

    /**
     * @param  array{booking_round_id?: int|string|null, faculty?: string|null}  $filters
     * @return list<array{product_id: int|null, product_name: string, choice_label: string, choice_value: string, qty: int}>
     */
    public function summarize(array $filters = []): array
    {
        $orders = $this->countedOrders($filters)->load('items');
        $buckets = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $choices = is_array($item->choices) ? $item->choices : [];

                if ($choices === []) {
                    $this->addRow($buckets, $item->product_id, $item->name, '', '', $item->qty);

                    continue;
                }

                foreach ($choices as $choice) {
                    if (! is_array($choice)) {
                        continue;
                    }

                    $this->addRow(
                        $buckets,
                        $item->product_id,
                        $item->name,
                        (string) ($choice['label'] ?? ''),
                        (string) ($choice['value'] ?? ''),
                        $item->qty,
                    );
                }
            }
        }

        $rows = array_values($buckets);

        usort($rows, function (array $left, array $right): int {
            return [$left['product_name'], $left['choice_label'], $left['choice_value']]
                <=> [$right['product_name'], $right['choice_label'], $right['choice_value']];
        });

        return $rows;
    }

    /**
     * @param  array{booking_round_id?: int|string|null}  $filters
     * @return list<string>
     */
    public function faculties(array $filters = []): array
    {
        $fromOrders = $this->countedOrders($filters)
            ->pluck('faculty')
            ->filter(fn (mixed $faculty): bool => is_string($faculty) && $faculty !== '')
            ->unique()
            ->values();

        return Collection::make($this->checkout->faculties())
            ->concat($fromOrders)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{booking_round_id?: int|string|null, faculty?: string|null}  $filters
     * @return Collection<int, Order>
     */
    private function countedOrders(array $filters): Collection
    {
        return Order::query()
            ->whereIn('status', $this->countedStatuses())
            ->when(filled($filters['booking_round_id'] ?? null), function ($query) use ($filters): void {
                $query->where('booking_round_id', $filters['booking_round_id']);
            })
            ->when(filled($filters['faculty'] ?? null), function ($query) use ($filters): void {
                $query->where('faculty', $filters['faculty']);
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, array{product_id: int|null, product_name: string, choice_label: string, choice_value: string, qty: int}>  $buckets
     */
    private function addRow(array &$buckets, mixed $productId, string $productName, string $label, string $value, int $qty): void
    {
        $key = implode("\u{1f}", [(string) $productId, $productName, $label, $value]);

        if (! isset($buckets[$key])) {
            $buckets[$key] = [
                'product_id' => is_numeric($productId) ? (int) $productId : null,
                'product_name' => $productName,
                'choice_label' => $label,
                'choice_value' => $value,
                'qty' => 0,
            ];
        }

        $buckets[$key]['qty'] += $qty;
    }
}
