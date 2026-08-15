<?php

use App\Services\Cart\CartService;
use App\Services\Line\LineIdentityService;
use App\Services\Order\OrderService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('คำสั่งซื้อ')] class extends Component
{
    public function mount(OrderService $orders, LineIdentityService $line): void
    {
        if ($line->userId() !== null) {
            return;
        }

        $order = $orders->trackedGuestOrder();

        if ($order === null) {
            return;
        }

        $this->redirect(route('orders.confirmation', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]));
    }

    public function render(CartService $cart, LineIdentityService $line)
    {
        $lineOrders = $line->ordersForCurrentUser();

        return $this->view([
            'cartCount' => $cart->count(),
            'hasLineIdentity' => $line->userId() !== null,
            'lineOrders' => $lineOrders,
        ]);
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

        @if ($hasLineIdentity)
            @if ($lineOrders->isEmpty())
                <section class="mt-8 text-center" role="status">
                    <x-icon name="shopping-bag" size="xl" class="mx-auto text-muted" />
                    <p class="mt-4 text-sm font-medium text-accent">ยังไม่มีการจอง</p>
                    <h2 class="mt-2 text-xl font-semibold">ยังไม่มีคำสั่งซื้อ</h2>
                    <p class="mt-2 text-sm text-muted">เมื่อจองผ่าน LINE และแนบสลิปแล้ว สถานะจะแสดงที่นี่</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex min-h-11 items-center rounded-brand border border-border bg-surface px-4 font-medium hover:bg-bg">ไปหน้าหลัก</a>
                </section>
            @else
                <ul class="mt-6 space-y-3" aria-label="ออเดอร์ของฉัน">
                    @foreach ($lineOrders as $order)
                        <li>
                            <a
                                href="{{ route('orders.confirmation', ['order' => $order, 'token' => $order->tracking_token]) }}"
                                class="block rounded-brand border border-border bg-surface p-4 hover:border-accent"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium">{{ $order->number }}</p>
                                        <p class="mt-1 text-sm text-muted">{{ $order->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <p class="text-right text-sm font-medium">{{ number_format((float) $order->total, 2) }} บาท</p>
                                </div>
                                <p class="mt-3 text-sm text-accent">{{ $order->status->label() }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            <section class="mt-8 text-center" role="status">
                <x-icon name="shopping-bag" size="xl" class="mx-auto text-muted" />
                <p class="mt-4 text-sm font-medium text-accent">ยังไม่มีการจอง</p>
                <h2 class="mt-2 text-xl font-semibold">ยังไม่มีคำสั่งซื้อ</h2>
                <p class="mt-2 text-sm text-muted">เมื่อจองและแนบสลิปแล้ว สถานะจะแสดงที่นี่</p>
                <a href="{{ route('home') }}" class="mt-5 inline-flex min-h-11 items-center rounded-brand border border-border bg-surface px-4 font-medium hover:bg-bg">ไปหน้าหลัก</a>
            </section>
        @endif
    </main>

    <x-storefront.tabbar />
</div>
