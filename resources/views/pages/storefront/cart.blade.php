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

    <main id="content" class="mx-auto max-w-lg px-4 py-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" wire:navigate class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-surface" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </a>
            <h1 class="text-xl font-semibold">ตะกร้า</h1>
        </div>

        @if ($items->isEmpty())
            <div class="mt-6 flex flex-col items-center text-center">
                <x-icon name="shopping-cart" size="xl" class="text-muted" />
                <p class="mt-4 text-sm text-muted">ยังไม่มีสินค้าในตะกร้า</p>
                <a href="{{ route('home') }}" wire:navigate class="mt-4 inline-flex min-h-11 items-center rounded-brand bg-accent px-4 font-medium text-brand-fg hover:bg-accent-press">ไปเลือกสินค้า</a>
            </div>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($items as $item)
                    <li class="rounded-brand border border-border bg-surface p-4" wire:key="cart-{{ $item['id'] }}">
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
                                <button
                                    type="button"
                                    wire:click="decrement('{{ $item['id'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="decrement('{{ $item['id'] }}')"
                                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand border border-border hover:border-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    aria-label="ลดจำนวน"
                                >−</button>
                                <span class="min-w-8 text-center">{{ $item['qty'] }}</span>
                                <button
                                    type="button"
                                    wire:click="increment('{{ $item['id'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="increment('{{ $item['id'] }}')"
                                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand border border-border hover:border-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    aria-label="เพิ่มจำนวน"
                                >+</button>
                            </div>
                            <button
                                type="button"
                                wire:click="remove('{{ $item['id'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="remove('{{ $item['id'] }}')"
                                class="inline-flex min-h-11 items-center text-sm text-muted hover:text-fg disabled:cursor-not-allowed disabled:opacity-60"
                            >ลบรายการ</button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <section class="mt-6 rounded-brand border border-border bg-surface p-4">
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
                    <span class="text-brand">฿{{ number_format((float) $subtotal, 2) }}</span>
                </div>
            </section>
        @endif
    </main>

    @if ($items->isNotEmpty())
        <div class="fixed inset-x-0 bottom-14 z-10 border-t border-border bg-surface">
            <div class="mx-auto max-w-lg px-4 py-3">
                <a href="{{ route('checkout') }}" wire:navigate class="inline-flex min-h-11 w-full items-center justify-center rounded-brand bg-accent px-4 font-medium text-brand-fg hover:bg-accent-press">ดำเนินการจอง</a>
            </div>
        </div>
    @endif

    <x-storefront.tabbar />
</div>
