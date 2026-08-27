<?php

namespace App\Services\Packing;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Checkout\CheckoutService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PackingChecklistService
{
    public function __construct(private CheckoutService $checkout) {}

    /**
     * @param  array{booking_round_id?: int|string|null, fulfillment?: string|FulfillmentMethod|null, faculty?: string|null}  $filters
     * @return Collection<int, Order>
     */
    public function orders(array $filters = []): Collection
    {
        return $this->packableQuery($filters)
            ->with('items')
            ->orderByRaw("CASE fulfillment WHEN 'post' THEN 0 WHEN 'bookstore' THEN 1 WHEN 'hall' THEN 2 ELSE 3 END")
            ->orderBy('faculty')
            ->orderBy('number')
            ->get();
    }

    /**
     * @param  array{booking_round_id?: int|string|null, fulfillment?: string|FulfillmentMethod|null, faculty?: string|null}  $filters
     * @return list<string>
     */
    public function faculties(array $filters = []): array
    {
        $fromOrders = $this->packableQuery([
            'booking_round_id' => $filters['booking_round_id'] ?? null,
            'fulfillment' => $filters['fulfillment'] ?? null,
        ])
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
     * @return list<FulfillmentMethod>
     */
    public function channels(): array
    {
        return FulfillmentMethod::cases();
    }

    /**
     * @param  array{booking_round_id?: int|string|null, fulfillment?: string|FulfillmentMethod|null, faculty?: string|null}  $filters
     * @return Builder<Order>
     */
    private function packableQuery(array $filters = []): Builder
    {
        $fulfillment = $this->fulfillmentFilter($filters);

        return Order::query()
            ->where(function (Builder $query): void {
                $query->whereIn('status', [
                    OrderStatus::Confirmed,
                    OrderStatus::ReadyForPickup,
                ])->orWhere(function (Builder $query): void {
                    $query->where('status', OrderStatus::Shipped)
                        ->where('fulfillment', FulfillmentMethod::Post)
                        ->whereNull('parcel_number');
                });
            })
            ->when(filled($filters['booking_round_id'] ?? null), function (Builder $query) use ($filters): void {
                $query->where('booking_round_id', $filters['booking_round_id']);
            })
            ->when($fulfillment !== null, function (Builder $query) use ($fulfillment): void {
                $query->where('fulfillment', $fulfillment);
            })
            ->when(filled($filters['faculty'] ?? null), function (Builder $query) use ($filters): void {
                $query->where('faculty', $filters['faculty']);
            });
    }

    /**
     * @param  array{fulfillment?: string|FulfillmentMethod|null}  $filters
     */
    private function fulfillmentFilter(array $filters): ?FulfillmentMethod
    {
        $value = $filters['fulfillment'] ?? null;

        if ($value instanceof FulfillmentMethod) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        return FulfillmentMethod::tryFrom($value);
    }
}
