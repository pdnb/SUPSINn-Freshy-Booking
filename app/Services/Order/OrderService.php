<?php

namespace App\Services\Order;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\SlipVerificationResult;
use App\Models\Order;
use App\Models\User;
use App\Services\Booking\BookingRoundService;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Line\LineIdentityService;
use App\Services\Payment\SlipVerificationService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private CheckoutService $checkout,
        private SlipVerificationService $slips,
        private BookingRoundService $booking,
        private LineIdentityService $line,
        private OrderNumberGenerator $numbers,
        private Session $session,
    ) {}

    public function findForGuestTracking(string $number, string $token): ?Order
    {
        $order = Order::query()
            ->where('number', $number)
            ->with(['items', 'slip'])
            ->first();

        if ($order === null || ! $this->trackingTokenMatches($order, $token)) {
            return null;
        }

        return $order;
    }

    /**
     * @return Collection<int, Order>
     */
    public function findForGuestLookup(string $studentId, string $phone): Collection
    {
        return Order::query()
            ->where('student_id', $studentId)
            ->where('phone', $phone)
            ->with(['items', 'slip'])
            ->latest()
            ->get();
    }

    public function rememberGuestTracking(Order $order, bool $autoOpen = false): void
    {
        $existing = $this->session->get('order.tracking');
        $preserveAutoOpen = ! $autoOpen
            && is_array($existing)
            && ($existing['number'] ?? null) === $order->number
            && ($existing['auto_open'] ?? false) === true;

        $this->session->put('order.tracking', [
            'number' => $order->number,
            'token' => $order->tracking_token,
            'auto_open' => $autoOpen || $preserveAutoOpen,
        ]);
    }

    public function shouldAutoOpenTrackedOrder(): bool
    {
        $tracking = $this->session->get('order.tracking');

        return is_array($tracking) && ($tracking['auto_open'] ?? false) === true;
    }

    public function clearAutoOpenTrackedOrder(): void
    {
        $tracking = $this->session->get('order.tracking');

        if (! is_array($tracking)) {
            return;
        }

        $tracking['auto_open'] = false;
        $this->session->put('order.tracking', $tracking);
    }

    public function trackedGuestOrder(): ?Order
    {
        $tracking = $this->session->get('order.tracking');

        if (! is_array($tracking) || ! isset($tracking['number'], $tracking['token'])) {
            return null;
        }

        $order = $this->findForGuestTracking((string) $tracking['number'], (string) $tracking['token']);

        if ($order === null) {
            $this->session->forget('order.tracking');
        }

        return $order;
    }

    public function place(UploadedFile $slip): Order
    {
        $draft = $this->checkout->draft();

        if ($draft === null) {
            throw ValidationException::withMessages([
                'checkout' => 'กรุณากรอกข้อมูลการจองก่อน',
            ]);
        }

        $draft = $this->checkout->save($draft);

        $path = $slip->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'slip' => 'อัปโหลดสลิปไม่สำเร็จ',
            ]);
        }

        $checksum = $this->slips->inspect($path, $slip->getClientOriginalName());

        return DB::transaction(function () use ($slip, $draft, $checksum) {
            $order = Order::query()->create([
                'number' => $this->numbers->next(),
                'tracking_token' => Str::random(40),
                'line_user_id' => $this->line->userId(),
                'student_id' => $draft['student_id'],
                'full_name' => $draft['full_name'],
                'faculty' => $draft['faculty'],
                'major' => $draft['major'],
                'phone' => $draft['phone'],
                'fulfillment' => $draft['fulfillment'],
                'address' => $draft['address'] ?? null,
                'shipping_rate_id' => $draft['shipping_rate_id'] ?? null,
                'shipping_rate_name' => $draft['shipping_rate_name'] ?? null,
                'subtotal' => $draft['subtotal'],
                'shipping_amount' => $draft['shipping'],
                'total' => $draft['total'],
                'payment_mode' => $draft['payment_mode'] ?? PaymentMode::Full->value,
                'amount_due_now' => $draft['amount_due_now'] ?? $draft['total'],
                'amount_remaining' => $draft['amount_remaining'] ?? '0.00',
                'balance_collected_at' => null,
                'status' => OrderStatus::PendingReview,
                'booking_round_id' => $this->booking->openRounds()->first()?->id,
            ]);

            foreach ($this->cart->items() as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'choices' => $item['choices'],
                ]);
            }

            $stored = $slip->store('slips/'.$order->id, 'local');

            $order->slip()->create([
                'path' => $stored,
                'original_name' => $slip->getClientOriginalName(),
                'checksum' => $checksum,
                'verifier_result' => SlipVerificationResult::Pass,
            ]);

            $this->cart->clear();
            $this->checkout->forget();

            $order = $order->fresh(['items', 'slip']);

            $this->rememberGuestTracking($order, autoOpen: true);

            return $order;
        });
    }

    public function replaceSlip(Order $order, UploadedFile $slip): Order
    {
        if ($order->status !== OrderStatus::NeedReslip) {
            throw ValidationException::withMessages([
                'slip' => 'ไม่สามารถแนบสลิปใหม่ได้ในสถานะนี้',
            ]);
        }

        $path = $slip->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'slip' => 'อัปโหลดสลิปไม่สำเร็จ',
            ]);
        }

        $checksum = $this->slips->inspect($path, $slip->getClientOriginalName());

        return DB::transaction(function () use ($order, $slip, $checksum) {
            $existingSlip = $order->slip;

            if ($existingSlip !== null) {
                Storage::disk('local')->delete($existingSlip->path);
                $existingSlip->delete();
            }

            $stored = $slip->store('slips/'.$order->id, 'local');

            $order->slip()->create([
                'path' => $stored,
                'original_name' => $slip->getClientOriginalName(),
                'checksum' => $checksum,
                'verifier_result' => SlipVerificationResult::Pass,
            ]);

            $from = $order->status;

            $order->update(['status' => OrderStatus::PendingReview]);

            $order->statusChanges()->create([
                'from_status' => $from,
                'to_status' => OrderStatus::PendingReview,
                'user_id' => null,
            ]);

            return $order->fresh(['items', 'slip', 'statusChanges']);
        });
    }

    public function countPendingReview(): int
    {
        return Order::query()
            ->where('status', OrderStatus::PendingReview)
            ->count();
    }

    /**
     * @param  array{search?: string|null, status?: string|OrderStatus|null, statuses?: list<string|OrderStatus>|null, fulfillment?: string|FulfillmentMethod|null, booking_round_id?: int|string|null, date_from?: string|null, date_to?: string|null, awaiting_parcel?: bool}  $filters
     * @return Collection<int, Order>
     */
    public function queue(array $filters = []): Collection
    {
        $awaitingParcel = ($filters['awaiting_parcel'] ?? false) === true;

        $status = array_key_exists('status', $filters)
            ? $filters['status']
            : ($awaitingParcel ? null : OrderStatus::PendingReview);

        $statuses = $filters['statuses'] ?? null;

        return Order::query()
            ->with(['items', 'slip', 'bookingRound'])
            ->when(is_array($statuses) && $statuses !== [], function ($query) use ($statuses) {
                $query->whereIn('status', array_map(
                    fn (string|OrderStatus $status): string => $status instanceof OrderStatus ? $status->value : $status,
                    $statuses,
                ));
            }, function ($query) use ($status) {
                if ($this->statusFilter($status) !== null) {
                    $query->where('status', $this->statusFilter($status));
                }
            })
            ->when($awaitingParcel, function ($query) {
                $query->where(function ($query) {
                    $query->where('status', OrderStatus::Confirmed)
                        ->orWhere(function ($query) {
                            $query->where('status', OrderStatus::Shipped)
                                ->whereNull('parcel_number');
                        });
                });
            })
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function ($query) use ($search) {
                    $query->where('number', 'like', $search.'%')
                        ->orWhere('student_id', 'like', $search.'%')
                        ->orWhere('full_name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', $search.'%');
                });
            })
            ->when(filled($filters['booking_round_id'] ?? null), function ($query) use ($filters) {
                $query->where('booking_round_id', $filters['booking_round_id']);
            })
            ->when(filled($filters['fulfillment'] ?? null), function ($query) use ($filters) {
                $fulfillment = $filters['fulfillment'];
                $query->where('fulfillment', $fulfillment instanceof FulfillmentMethod
                    ? $fulfillment->value
                    : $fulfillment);
            })
            ->when(filled($filters['date_from'] ?? null), function ($query) use ($filters) {
                $from = Carbon::parse((string) $filters['date_from'], config('app.timezone'))->startOfDay();
                $query->where('created_at', '>=', $from);
            })
            ->when(filled($filters['date_to'] ?? null), function ($query) use ($filters) {
                $to = Carbon::parse((string) $filters['date_to'], config('app.timezone'))->endOfDay();
                $query->where('created_at', '<=', $to);
            })
            ->latest()
            ->get();
    }

    /**
     * @return list<OrderStatus>
     */
    public function allowedTransitions(Order $order): array
    {
        return match ($order->status) {
            OrderStatus::PendingReview => [OrderStatus::Confirmed, OrderStatus::NeedReslip, OrderStatus::Cancelled],
            OrderStatus::NeedReslip => [OrderStatus::PendingReview, OrderStatus::Cancelled],
            OrderStatus::Confirmed => $order->fulfillment->chargesShipping()
                ? [OrderStatus::Shipped, OrderStatus::Cancelled]
                : [OrderStatus::ReadyForPickup, OrderStatus::Cancelled],
            OrderStatus::ReadyForPickup => $order->hasOutstandingBalance()
                ? [OrderStatus::Cancelled]
                : [OrderStatus::Completed, OrderStatus::Cancelled],
            OrderStatus::Shipped => $order->hasOutstandingBalance()
                ? [OrderStatus::Cancelled]
                : [OrderStatus::Completed, OrderStatus::Cancelled],
            OrderStatus::Completed, OrderStatus::Cancelled => [],
        };
    }

    public function canIssueReceipt(Order $order): bool
    {
        return in_array($order->status, [
            OrderStatus::ReadyForPickup,
            OrderStatus::Shipped,
            OrderStatus::Completed,
        ], true);
    }

    public function transition(Order $order, OrderStatus $to, User $actor): Order
    {
        if (! in_array($to, $this->allowedTransitions($order), true)) {
            throw ValidationException::withMessages([
                'status' => 'เปลี่ยนเป็นสถานะนี้ไม่ได้',
            ]);
        }

        if ($to === OrderStatus::Completed && $order->hasOutstandingBalance()) {
            throw ValidationException::withMessages([
                'status' => 'ต้องบันทึกเก็บส่วนที่เหลือก่อนปิดออเดอร์',
            ]);
        }

        return DB::transaction(function () use ($order, $to, $actor) {
            $from = $order->status;

            $order->update(['status' => $to]);

            $order->statusChanges()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor->id,
            ]);

            return $order->fresh(['items', 'slip', 'bookingRound', 'statusChanges.user']);
        });
    }

    public function markShipped(Order $order, User $actor, ?string $parcelNumber = null): Order
    {
        if ($order->fulfillment !== FulfillmentMethod::Post) {
            throw ValidationException::withMessages([
                'fulfillment' => 'บันทึกเลขพัสดุได้เฉพาะออเดอร์ไปรษณีย์',
            ]);
        }

        $normalized = $this->normalizedParcelNumber($parcelNumber);

        return DB::transaction(function () use ($order, $actor, $normalized) {
            $order->update(['parcel_number' => $normalized]);

            return $this->transition($order->fresh(), OrderStatus::Shipped, $actor);
        });
    }

    public function updateParcelNumber(Order $order, User $actor, ?string $parcelNumber): Order
    {
        if ($order->fulfillment !== FulfillmentMethod::Post) {
            throw ValidationException::withMessages([
                'fulfillment' => 'บันทึกเลขพัสดุได้เฉพาะออเดอร์ไปรษณีย์',
            ]);
        }

        if ($order->status !== OrderStatus::Shipped) {
            throw ValidationException::withMessages([
                'parcel_number' => 'แก้ไขเลขพัสดุได้เมื่อจัดส่งแล้ว',
            ]);
        }

        $order->update(['parcel_number' => $this->normalizedParcelNumber($parcelNumber)]);

        return $order->fresh(['items', 'slip', 'bookingRound', 'statusChanges.user']);
    }

    public function issueReceipt(Order $order, User $actor): Order
    {
        if (! $this->canIssueReceipt($order)) {
            throw ValidationException::withMessages([
                'receipt' => 'ออกใบเสร็จได้เมื่อพร้อมรับของ จัดส่งแล้ว หรือรับของแล้วเท่านั้น',
            ]);
        }

        if ($order->receipt_issued_at === null) {
            $order->update(['receipt_issued_at' => now()]);
        }

        return $order->fresh(['items', 'slip', 'bookingRound', 'statusChanges.user']);
    }

    /**
     * @return list<OrderStatus>
     */
    public function reviewQueueStatuses(): array
    {
        return [OrderStatus::PendingReview, OrderStatus::NeedReslip];
    }

    public function nextInReviewQueue(Order $current): ?Order
    {
        return $this->queue([
            'status' => null,
            'statuses' => $this->reviewQueueStatuses(),
        ])->first(fn (Order $order): bool => $order->id !== $current->id);
    }

    public function collectBalance(Order $order, User $actor): Order
    {
        if (! $order->hasOutstandingBalance()) {
            throw ValidationException::withMessages([
                'balance' => 'ไม่มียอดคงเหลือที่ต้องเก็บ',
            ]);
        }

        $order->update(['balance_collected_at' => now()]);

        return $order->fresh(['items', 'slip', 'bookingRound', 'statusChanges.user']);
    }

    public function markPickedUp(Order $order, User $actor): Order
    {
        if ($order->status !== OrderStatus::ReadyForPickup) {
            throw ValidationException::withMessages([
                'status' => 'รับของได้เมื่อสถานะพร้อมรับของเท่านั้น',
            ]);
        }

        if ($order->hasOutstandingBalance()) {
            throw ValidationException::withMessages([
                'status' => 'ต้องบันทึกเก็บส่วนที่เหลือก่อนปิดออเดอร์',
            ]);
        }

        return DB::transaction(function () use ($order, $actor) {
            $this->issueReceipt($order, $actor);

            return $this->transition($order->fresh(), OrderStatus::Completed, $actor);
        });
    }

    private function statusFilter(string|OrderStatus|null $status): ?string
    {
        if ($status === null || $status === '' || $status === 'all') {
            return null;
        }

        return $status instanceof OrderStatus ? $status->value : $status;
    }

    private function normalizedParcelNumber(?string $parcelNumber): ?string
    {
        $trimmed = trim((string) $parcelNumber);

        if ($trimmed === '') {
            return null;
        }

        if (Str::length($trimmed) > 255) {
            throw ValidationException::withMessages([
                'parcel_number' => 'เลขพัสดุยาวเกินไป',
            ]);
        }

        return $trimmed;
    }

    private function trackingTokenMatches(Order $order, string $token): bool
    {
        return strlen($token) === strlen($order->tracking_token)
            && hash_equals($order->tracking_token, $token);
    }
}
