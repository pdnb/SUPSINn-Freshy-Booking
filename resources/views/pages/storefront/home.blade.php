<?php

use App\Services\Ads\AdsBannerService;
use App\Services\Booking\BookingRoundService;
use App\Services\Cart\CartService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('จองชุดเฟรชชี่')] class extends Component
{
    public function render(BookingRoundService $booking, CartService $cart, AdsBannerService $ads)
    {
        return $this->view([
            'isOpen' => $booking->openRounds()->isNotEmpty(),
            'products' => $booking->storefrontProducts(),
            'cartCount' => $cart->count(),
            'banners' => $ads->activeForStorefront(),
        ]);
    }
};
?>

<div class="min-h-dvh bg-bg pb-20 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg px-4 py-6">
        @if ($banners->isNotEmpty())
            <section
                class="mb-6 touch-pan-y overflow-hidden rounded-brand"
                data-od-id="home-banner"
                aria-roledescription="carousel"
                aria-label="แบนเนอร์"
                @if ($banners->count() > 1) tabindex="0" @endif
                x-data="{
                    index: 0,
                    count: {{ $banners->count() }},
                    startX: null,
                    swiped: false,
                    next() { this.index = (this.index + 1) % this.count },
                    prev() { this.index = (this.index - 1 + this.count) % this.count },
                    onPointerDown(event) {
                        this.startX = event.clientX;
                        this.swiped = false;
                    },
                    onPointerUp(event) {
                        if (this.startX === null || this.count < 2) {
                            return;
                        }

                        const deltaX = event.clientX - this.startX;
                        this.startX = null;

                        if (Math.abs(deltaX) < 40) {
                            return;
                        }

                        this.swiped = true;
                        deltaX < 0 ? this.next() : this.prev();
                    },
                    onClick(event) {
                        if (! this.swiped) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        this.swiped = false;
                    },
                }"
                @if ($banners->count() > 1)
                    x-init="setInterval(() => next(), 5000)"
                    x-on:pointerdown="onPointerDown($event)"
                    x-on:pointerup="onPointerUp($event)"
                    x-on:pointercancel="startX = null"
                    x-on:click.capture="onClick($event)"
                    x-on:keydown.left.prevent="prev()"
                    x-on:keydown.right.prevent="next()"
                @endif
            >
                <div class="relative aspect-[2/1]">
                    @foreach ($banners as $index => $banner)
                        @if (filled($banner->url))
                            <a
                                href="{{ $banner->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                wire:key="home-banner-{{ $banner->id }}"
                                x-show="index === {{ $index }}"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                x-cloak
                                class="absolute inset-0 block"
                            >
                                <img
                                    src="{{ $banner->imageUrl() }}"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    draggable="false"
                                >
                            </a>
                        @else
                            <div
                                wire:key="home-banner-{{ $banner->id }}"
                                x-show="index === {{ $index }}"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                x-cloak
                                class="absolute inset-0 block"
                            >
                                <img
                                    src="{{ $banner->imageUrl() }}"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    draggable="false"
                                >
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-brand border border-border bg-surface p-5">
            <p class="text-sm text-muted">ศูนย์หนังสือ มรส.</p>
            <h1 class="mt-1 text-xl font-semibold">จองชุดเฟรชชี่ออนไลน์</h1>
            <p class="mt-2 text-sm text-muted">รับสินค้าที่ร้านศูนย์หนังสือฯ หรือหอประชุมฯ วันรายงานตัว · ใบเสร็จได้รับตอนรับของ</p>
        </section>

        @if (! $isOpen)
            <section class="mt-6 rounded-brand border border-border bg-surface p-5" role="status">
                <h2 class="text-lg font-semibold">ยังไม่เปิดรับจอง</h2>
                <p class="mt-2 text-sm text-muted">ขณะนี้ไม่มีรอบที่เปิดอยู่ ใส่ตะกร้าหรือสั่งซื้อยังไม่ได้ กรุณากลับมาใหม่เมื่อถึงรอบจอง</p>
            </section>
        @else
            <section class="mt-6">
                <h2 class="text-base font-semibold">สินค้าจอง</h2>

                @if ($products->isEmpty())
                    <p class="mt-3 text-sm text-muted">รอบเปิดอยู่ แต่ยังไม่มีสินค้าในรอบนี้</p>
                @else
                    <ul class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ($products as $product)
                            <li wire:key="product-{{ $product->id }}">
                                <a
                                    href="{{ route('products.show', $product) }}"
                                    class="flex min-h-11 h-full flex-col overflow-hidden rounded-brand border border-border bg-surface hover:border-accent"
                                >
                                    @if ($product->coverImage)
                                        <img
                                            src="{{ $product->coverImage->url() }}"
                                            alt="{{ $product->name }}"
                                            class="aspect-[4/3] w-full rounded-t-[calc(var(--radius-brand)-1px)] object-cover"
                                        >
                                    @else
                                        <div class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-t-[calc(var(--radius-brand)-1px)] bg-border/40 text-sm text-muted" aria-hidden="true">
                                            {{ $product->type === \App\Enums\ProductType::Bundle ? 'ชุด' : 'สินค้า' }}
                                        </div>
                                    @endif
                                    <div class="flex flex-1 flex-col gap-1 p-3">
                                        <span class="inline-flex w-fit rounded-full bg-bg px-2 py-0.5 text-xs text-muted">
                                            {{ $product->type === \App\Enums\ProductType::Bundle ? 'ซื้อทั้งชุด' : 'ซื้อแยกได้' }}
                                        </span>
                                        <span class="font-medium">{{ $product->name }}</span>
                                        <span class="mt-auto text-sm text-muted">฿{{ number_format((float) $product->price, 2) }}</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="mt-8 rounded-brand border border-border bg-surface p-5">
                <h2 class="text-base font-semibold">ขั้นตอนสั้น ๆ</h2>
                <ol class="mt-3 space-y-2 text-sm">
                    <li>1. เลือกสินค้าและไซส์</li>
                    <li>2. กรอกรหัสนักศึกษา · ชื่อ · คณะ · เบอร์โทร</li>
                    <li>3. เลือกรับเองหรือไปรษณีย์</li>
                    <li>4. สแกน PromptPay และแนบสลิป</li>
                </ol>
            </section>
        @endif
    </main>

    <x-storefront.tabbar />
</div>
