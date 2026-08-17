<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('คำสั่งซื้อ')] class extends Component
{
    #[Locked]
    public int $orderId;

    public function mount(string $order, string $token, OrderService $orders): void
    {
        $tracked = $orders->findForGuestTracking($order, $token);

        if ($tracked === null) {
            abort(404);
        }

        $orders->rememberGuestTracking($tracked);

        $this->orderId = $tracked->id;
    }

    public function render(CartService $cart)
    {
        $order = Order::query()->with(['items', 'slip'])->findOrFail($this->orderId);

        return $this->view([
            'order' => $order,
            'cartCount' => $cart->count(),
            'steps' => $this->trackingSteps($order),
            'receiptNote' => $order->receipt_issued_at !== null
                ? 'ออกใบเสร็จแล้ว '.$order->receipt_issued_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                : 'ใบเสร็จจะได้รับตอนรับสินค้า',
        ]);
    }

    /**
     * @return list<array{label: string, state: 'done'|'current'|'upcoming'}>
     */
    private function trackingSteps(Order $order): array
    {
        $labels = ['จองแล้ว', 'ตรวจสลิป', 'พร้อมรับ', 'รับแล้ว'];

        if ($order->status === OrderStatus::Completed) {
            return collect($labels)
                ->map(fn (string $label): array => ['label' => $label, 'state' => 'done'])
                ->all();
        }

        $currentIndex = match ($order->status) {
            OrderStatus::PendingReview, OrderStatus::NeedReslip, OrderStatus::Cancelled => 1,
            OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::ReadyForPickup => 2,
        };

        return collect($labels)->map(function (string $label, int $index) use ($currentIndex): array {
            return [
                'label' => $label,
                'state' => match (true) {
                    $index < $currentIndex => 'done',
                    $index === $currentIndex => 'current',
                    default => 'upcoming',
                },
            ];
        })->all();
    }
};
?>

<div class="min-h-dvh bg-bg pb-20 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg px-4 py-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-surface" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </a>
            <h1 class="text-xl font-semibold">คำสั่งซื้อ</h1>
        </div>

        <ol class="mt-4 grid grid-cols-4 gap-1.5 text-center text-[11px]" aria-label="สถานะคำสั่งซื้อ">
            @foreach ($steps as $step)
                <li
                    @class([
                        'rounded-brand px-1 py-2 font-medium',
                        'bg-accent text-accent-fg' => $step['state'] === 'current',
                        'bg-accent/15 text-accent' => $step['state'] === 'done',
                        'bg-border/50 text-muted' => $step['state'] === 'upcoming',
                    ])
                    @if ($step['state'] === 'current') aria-current="step" @endif
                >
                    {{ $step['label'] }}
                </li>
            @endforeach
        </ol>

        <section class="mt-4 rounded-brand border border-border bg-surface p-4">
            <p class="flex items-center justify-between gap-3 text-xs font-medium text-accent">
                <span>รหัสออเดอร์</span>
                <button
                    type="button"
                    class="-mr-1 inline-flex min-h-11 items-center gap-1 rounded-brand px-1 hover:bg-bg"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(@js($order->number)).then(() => { copied = true; setTimeout(() => copied = false, 1600) })"
                    aria-label="คัดลอกรหัสออเดอร์ {{ $order->number }}"
                    x-bind:aria-label="copied ? 'คัดลอกรหัสออเดอร์แล้ว' : @js('คัดลอกรหัสออเดอร์ '.$order->number)"
                >
                    <span x-text="copied ? 'คัดลอกแล้ว' : @js($order->number)">{{ $order->number }}</span>
                    <x-icon name="clipboard-document" size="sm" />
                </button>
            </p>
            <h2 class="mt-1 text-xl font-semibold">{{ $order->status->label() }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $receiptNote }}</p>
            @if ($order->status === \App\Enums\OrderStatus::PendingReview)
                <p class="mt-3 text-sm text-muted">สลิปผ่านการตรวจเบื้องต้นแล้ว รอเจ้าหน้าที่ยืนยัน — ยังไม่ถือว่าชำระแล้ว</p>
            @endif
        </section>

        <section class="mt-4 rounded-brand border border-border bg-surface px-4">
            <dl class="text-sm">
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">ผู้จอง</dt>
                    <dd class="text-right">{{ $order->full_name }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">รหัสนักศึกษา</dt>
                    <dd class="text-right">{{ $order->student_id }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">คณะ</dt>
                    <dd class="text-right">{{ $order->faculty }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">สาขาวิชา</dt>
                    <dd class="text-right">{{ $order->major }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">เบอร์โทร</dt>
                    <dd class="text-right">{{ $order->phone }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">วิธีรับ</dt>
                    <dd class="text-right">{{ $order->fulfillment->label() }}</dd>
                </div>
                @if ($order->address)
                    <div class="flex justify-between gap-3 border-b border-border py-2">
                        <dt class="text-muted">ที่อยู่</dt>
                        <dd class="text-right">{{ $order->address }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3 py-2">
                    <dt class="text-muted">สลิป</dt>
                    <dd class="min-w-0 text-right">
                        @if ($order->slip)
                            <div x-data="{ open: false }">
                                <button
                                    type="button"
                                    class="-my-1 inline-flex min-h-11 max-w-full items-center justify-end break-all text-right text-accent hover:underline"
                                    x-on:click="open = true; $nextTick(() => $refs.preview.showModal())"
                                    aria-haspopup="dialog"
                                    aria-controls="slip-preview"
                                    aria-label="ดูสลิป {{ $order->slip->original_name }}"
                                >
                                    {{ $order->slip->original_name }}
                                </button>
                                <dialog
                                    id="slip-preview"
                                    x-ref="preview"
                                    class="m-auto w-[min(100%,32rem)] max-w-[calc(100%-2rem)] rounded-brand border border-border bg-surface p-4 text-fg backdrop:bg-fg/50"
                                    x-on:close="open = false"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <h2 class="text-sm font-medium">สลิป</h2>
                                        <button
                                            type="button"
                                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-bg"
                                            x-on:click="$refs.preview.close()"
                                            aria-label="ปิดตัวอย่างสลิป"
                                        >
                                            <x-icon name="x-mark" size="md" />
                                        </button>
                                    </div>
                                    @if (str_ends_with(mb_strtolower($order->slip->original_name), '.pdf'))
                                        <iframe
                                            x-show="open"
                                            src="{{ route('orders.slip', $order) }}"
                                            title="สลิป {{ $order->slip->original_name }}"
                                            class="mt-3 h-[70vh] w-full rounded-brand border border-border bg-bg"
                                        ></iframe>
                                    @else
                                        <img
                                            x-show="open"
                                            src="{{ route('orders.slip', $order) }}"
                                            alt="สลิป {{ $order->slip->original_name }}"
                                            class="mt-3 max-h-[70vh] w-full object-contain"
                                        >
                                    @endif
                                </dialog>
                            </div>
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="mt-5">
            <h2 class="font-semibold">รายการที่จอง</h2>
            <ul class="mt-3 space-y-3">
                @forelse ($order->items as $item)
                    <li wire:key="guest-item-{{ $item->id }}" class="rounded-brand border border-border bg-surface p-4 text-sm">
                        <div class="flex justify-between gap-3 font-medium">
                            <span>{{ $item->name }}</span>
                            <span>× {{ $item->qty }}</span>
                        </div>
                        @if ($item->choices)
                            <ul class="mt-2 space-y-0.5 text-muted">
                                @foreach ($item->choices as $index => $choice)
                                    <li wire:key="guest-choice-{{ $item->id }}-{{ $index }}">
                                        @if (is_array($choice) && isset($choice['label'], $choice['value']))
                                            {{ $choice['label'] }} · {{ $choice['value'] }}
                                        @else
                                            {{ is_scalar($choice) ? $choice : collect($choice)->flatten()->filter()->implode(' · ') }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="mt-1 text-right text-muted">{{ number_format((float) $item->price, 2) }} บาท</p>
                    </li>
                @empty
                    <li class="text-sm text-muted">ไม่มีรายการ</li>
                @endforelse
            </ul>
            <dl class="mt-4 space-y-2 rounded-brand border border-border bg-surface p-4 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">ยอดสินค้า</dt>
                    <dd>{{ number_format((float) $order->subtotal, 2) }} บาท</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">ค่าส่ง</dt>
                    <dd>{{ number_format((float) $order->shipping_amount, 2) }} บาท</dd>
                </div>
                <div class="flex justify-between gap-3 border-t border-border pt-2 font-medium">
                    <dt>รวม</dt>
                    <dd>{{ number_format((float) $order->total, 2) }} บาท</dd>
                </div>
                @if ($order->payment_mode === \App\Enums\PaymentMode::Deposit)
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">จ่ายตอนสั่ง (มัดจำ)</dt>
                        <dd>{{ number_format((float) $order->amount_due_now, 2) }} บาท</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">คงเหลือตอนรับ</dt>
                        <dd>{{ number_format((float) $order->amount_remaining, 2) }} บาท</dd>
                    </div>
                @endif
            </dl>
        </section>

        @if (! $order->fulfillment->chargesShipping())
            <section class="mt-6">
                <h2 class="font-semibold">จุดรับสินค้า</h2>
                <div class="mt-3 space-y-3">
                    <article class="rounded-brand border border-border bg-surface p-4">
                        <h3 class="font-medium">{{ \App\Enums\FulfillmentMethod::Bookstore->label() }}</h3>
                        <p class="mt-1 text-sm text-muted">{{ \App\Enums\FulfillmentMethod::Bookstore->caption() }}</p>
                    </article>
                    <article class="rounded-brand border border-border bg-surface p-4">
                        <h3 class="font-medium">{{ \App\Enums\FulfillmentMethod::Hall->label() }}</h3>
                        <p class="mt-1 text-sm text-muted">{{ \App\Enums\FulfillmentMethod::Hall->caption() }}</p>
                    </article>
                </div>
            </section>
        @endif
    </main>

    <x-storefront.tabbar />
</div>
