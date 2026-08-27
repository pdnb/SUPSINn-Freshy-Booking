<?php

namespace App\Services\Packing;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use App\Services\Order\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackingChecklistService
{
    public function __construct(
        private CheckoutService $checkout,
        private OrderService $orders,
    ) {}

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
     * @return Collection<int, Order>
     */
    public function packedToday(): Collection
    {
        return Order::query()
            ->where('packed_at', '>=', now()->startOfDay())
            ->orderByDesc('packed_at')
            ->get();
    }

    public function markPacked(string $number, User $actor): Order
    {
        $order = $this->orderByNumber($number);

        if ($order->packed_at !== null) {
            throw ValidationException::withMessages([
                'packNumber' => 'ออเดอร์นี้แพ็คแล้ว',
            ]);
        }

        if (! $this->hasPackableStatus($order)) {
            throw ValidationException::withMessages([
                'packNumber' => 'ออเดอร์นี้ไม่ได้อยู่ในกองแพ็ค',
            ]);
        }

        return DB::transaction(function () use ($order, $actor): Order {
            $order->update(['packed_at' => now()]);

            if ($this->isPickup($order) && $order->status === OrderStatus::Confirmed) {
                return $this->orders->transition($order->fresh(), OrderStatus::ReadyForPickup, $actor);
            }

            return $order->fresh();
        });
    }

    public function unmarkPacked(string $number, User $actor): Order
    {
        $order = $this->orderByNumber($number);

        if ($order->packed_at === null) {
            throw ValidationException::withMessages([
                'packNumber' => 'ออเดอร์นี้ยังไม่ได้แพ็ค',
            ]);
        }

        if ($order->status === OrderStatus::Completed) {
            throw ValidationException::withMessages([
                'packNumber' => 'รับของแล้ว ยกเลิกแพ็คไม่ได้',
            ]);
        }

        return DB::transaction(function () use ($order, $actor): Order {
            $order->update(['packed_at' => null]);

            if ($this->isPickup($order) && $order->status === OrderStatus::ReadyForPickup) {
                return $this->orders->transition($order->fresh(), OrderStatus::Confirmed, $actor);
            }

            return $order->fresh();
        });
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

    private function isPickup(Order $order): bool
    {
        return ! $order->fulfillment->chargesShipping();
    }

    private function orderByNumber(string $number): Order
    {
        $number = trim($number);

        $order = Order::query()->where('number', $number)->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'packNumber' => 'ไม่พบออเดอร์นี้',
            ]);
        }

        return $order;
    }

    private function hasPackableStatus(Order $order): bool
    {
        if (in_array($order->status, [OrderStatus::Confirmed, OrderStatus::ReadyForPickup], true)) {
            return true;
        }

        return $order->status === OrderStatus::Shipped
            && $order->fulfillment === FulfillmentMethod::Post
            && $order->parcel_number === null;
    }

    /**
     * @param  array{booking_round_id?: int|string|null, fulfillment?: string|FulfillmentMethod|null, faculty?: string|null}  $filters
     * @return Builder<Order>
     */
    private function packableQuery(array $filters = []): Builder
    {
        $fulfillment = $this->fulfillmentFilter($filters);

        return Order::query()
            ->whereNull('packed_at')
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
