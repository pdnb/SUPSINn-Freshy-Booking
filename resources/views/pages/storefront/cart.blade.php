<?php

use App\Services\Cart\CartService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('ตะกร้า')] class extends Component
{
    public function increment(string $itemId, CartService $cart): void
    {
        $item = $cart->items()->firstWhere('id', $itemId);

        if (! $item) {
            return;
        }

        $this->changeQty($itemId, (int) $item['qty'] + 1, $cart);
    }

    public function decrement(string $itemId, CartService $cart): void
    {
        $item = $cart->items()->firstWhere('id', $itemId);

        if (! $item) {
            return;
        }

        if ((int) $item['qty'] <= 1) {
            $cart->remove($itemId);

            return;
        }

        $this->changeQty($itemId, (int) $item['qty'] - 1, $cart);
    }

    private function changeQty(string $itemId, int $qty, CartService $cart): void
    {
        try {
            $cart->updateQty($itemId, $qty);
        } catch (ValidationException $exception) {
            $this->dispatch('storefront-toast', message: $exception->validator->errors()->first());
        }
    }

    public function remove(string $itemId, CartService $cart): void
    {
        $cart->remove($itemId);
    }

    public function render(CartService $cart)
    {
        return $this->view([
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'cartCount' => $cart->count(),
        ]);
    }
};
?>

<div class="min-h-dvh bg-bg pb-40 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg p-4">
        <div class="flex items-center gap-2">
            <x-storefront.button variant="ghost" :href="route('home')" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </x-storefront.button>
            <h1 class="text-xl font-semibold">ตะกร้า</h1>
        </div>

        @if ($items->isEmpty())
            <x-storefront.empty-state class="mt-6" icon="shopping-cart" description="ยังไม่มีสินค้าในตะกร้า">
                <x-storefront.button :href="route('home')">ไปเลือกสินค้า</x-storefront.button>
            </x-storefront.empty-state>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($items as $item)
                    <x-storefront.card as="li" wire:key="cart-{{ $item['id'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-medium">{{ $item['name'] }}</p>
                            <p class="text-sm">฿{{ number_format((float) $item['price'], 2) }}</p>
                        </div>
                        @if ($item['choices'] !== [])
                            <ul class="mt-2 space-y-0.5 text-sm text-muted">
                                @foreach ($item['choices'] as $choice)
                                    <li>{{ $choice['label'] }} · {{ $choice['value'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2" role="group" aria-label="จำนวน {{ $item['name'] }}">
                                <x-storefront.button
                                    variant="secondary"
                                    size="icon"
                                    wire:click="decrement('{{ $item['id'] }}')"
                                    aria-label="ลดจำนวน"
                                >−</x-storefront.button>
                                <span class="min-w-8 text-center">{{ $item['qty'] }}</span>
                                <x-storefront.button
                                    variant="secondary"
                                    size="icon"
                                    wire:click="increment('{{ $item['id'] }}')"
                                    aria-label="เพิ่มจำนวน"
                                >+</x-storefront.button>
                            </div>
                            <x-storefront.button
                                variant="quiet"
                                wire:click="remove('{{ $item['id'] }}')"
                            >ลบรายการ</x-storefront.button>
                        </div>
                    </x-storefront.card>
                @endforeach
            </ul>

            <x-storefront.card as="section" class="mt-6">
                <div class="flex justify-between gap-3 text-sm">
                    <span>ราคาสินค้า</span>
                    <span>฿{{ number_format((float) $subtotal, 2) }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm text-muted">
                    <span>ค่าจัดส่ง</span>
                    <span>คำนวณตอนชำระถ้าส่งไปรษณีย์</span>
                </div>
                <div class="mt-3 flex justify-between gap-3 font-medium">
                    <span>ยอดรวมสินค้า</span>
                    <x-storefront.price :amount="$subtotal" />
                </div>
            </x-storefront.card>
        @endif
    </main>

    @if ($items->isNotEmpty())
        <x-storefront.bottom-bar>
            <x-storefront.button :href="route('checkout')" block>ดำเนินการจอง</x-storefront.button>
        </x-storefront.bottom-bar>
    @endif

    <x-storefront.tabbar />
</div>
