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
        ]), navigate: true);
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
            <x-storefront.button variant="ghost" :href="route('home')" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </x-storefront.button>
            <h1 class="text-xl font-semibold">คำสั่งซื้อ</h1>
        </div>

        @if ($hasLineIdentity)
            @if ($lineOrders->isEmpty())
                <x-storefront.empty-state
                    class="mt-8"
                    icon="shopping-bag"
                    kicker="ยังไม่มีการจอง"
                    title="ยังไม่มีคำสั่งซื้อ"
                    description="เมื่อจองผ่าน LINE และแนบสลิปแล้ว สถานะจะแสดงที่นี่"
                >
                    <x-storefront.button variant="secondary" :href="route('home')">ไปหน้าหลัก</x-storefront.button>
                </x-storefront.empty-state>
            @else
                <ul class="mt-6 space-y-3" aria-label="ออเดอร์ของฉัน">
                    @foreach ($lineOrders as $order)
                        <li>
                            <x-storefront.card
                                as="a"
                                href="{{ route('orders.confirmation', ['order' => $order, 'token' => $order->tracking_token]) }}"
                                wire:navigate
                                class="block hover:border-accent"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium">{{ $order->number }}</p>
                                        <p class="mt-1 text-sm text-muted">{{ $order->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <p class="text-right text-sm font-medium">{{ number_format((float) $order->total, 2) }} บาท</p>
                                </div>
                                <p class="mt-3 text-sm text-brand">{{ $order->status->label() }}</p>
                            </x-storefront.card>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            <x-storefront.empty-state
                class="mt-8"
                icon="shopping-bag"
                kicker="ยังไม่มีการจอง"
                title="ยังไม่มีคำสั่งซื้อ"
                description="เมื่อจองและแนบสลิปแล้ว สถานะจะแสดงที่นี่"
            >
                <x-storefront.button variant="secondary" :href="route('home')">ไปหน้าหลัก</x-storefront.button>
            </x-storefront.empty-state>
        @endif
    </main>

    <x-storefront.tabbar />
</div>
