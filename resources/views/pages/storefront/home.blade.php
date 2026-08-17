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

        <x-storefront.card as="section" padding="5">
            <h1 class="inline-block bg-linear-to-br from-brand to-highlight bg-clip-text text-xl font-semibold text-transparent">
                ระบบจองชุดเฟรชชี่
            </h1>
            <p class="mt-2 text-sm text-highlight-fg">สำนักจัดการทรัพย์สิน มหาวิทยาลัยราชภัฏสุราษฎร์ธานี</p>
        </x-storefront.card>

        @if (! $isOpen)
            <x-storefront.card as="section" padding="5" class="mt-6" role="status">
                <h2 class="text-lg font-semibold">ยังไม่เปิดรับจอง</h2>
                <p class="mt-2 text-sm text-muted">ขณะนี้ไม่มีรอบที่เปิดอยู่ ใส่ตะกร้าหรือสั่งซื้อยังไม่ได้ กรุณากลับมาใหม่เมื่อถึงรอบจอง</p>
            </x-storefront.card>
        @else
            <section class="mt-6">
                <h2 class="text-base font-semibold">สินค้าเปิดจอง</h2>

                @if ($products->isEmpty())
                    <p class="mt-3 text-sm text-muted">รอบเปิดอยู่ แต่ยังไม่มีสินค้าในรอบนี้</p>
                @else
                    <ul class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ($products as $product)
                            <li wire:key="product-{{ $product->id }}">
                                <x-storefront.card
                                    as="a"
                                    href="{{ route('products.show', $product) }}"
                                    wire:navigate
                                    padding="0"
                                    class="flex h-full min-h-11 flex-col overflow-hidden hover:border-accent"
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
                                        <x-storefront.badge>
                                            {{ $product->type === \App\Enums\ProductType::Bundle ? 'ซื้อทั้งชุด' : 'ซื้อแยกได้' }}
                                        </x-storefront.badge>
                                        <span class="font-medium">{{ $product->name }}</span>
                                        <x-storefront.price :amount="$product->price" size="sm" class="mt-auto" />
                                    </div>
                                </x-storefront.card>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <x-storefront.card as="section" padding="5" class="mt-8" aria-labelledby="home-steps-heading">
                <h2 id="home-steps-heading" class="text-base font-semibold">ขั้นตอนการจอง</h2>
                <p class="mt-1 text-sm text-muted">จองครบใน 4 ขั้น ไม่ต้องสมัครบัญชี</p>

                <ol class="mt-5">
                    <li class="relative flex gap-3 pb-5">
                        <div class="flex w-9 shrink-0 flex-col items-center" aria-hidden="true">
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-accent text-sm font-semibold text-brand-fg">1</span>
                            <span class="mt-2 w-px flex-1 bg-border"></span>
                        </div>
                        <div class="min-w-0 flex-1 pt-1">
                            <div class="flex items-start gap-2">
                                <div>
                                    <p class="font-medium leading-snug">เลือกสินค้าและไซส์</p>
                                    <p class="mt-1 text-sm leading-relaxed text-muted">เลือกชุดหรือสินค้าแยก แล้วใส่ตะกร้า</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="relative flex gap-3 pb-5">
                        <div class="flex w-9 shrink-0 flex-col items-center" aria-hidden="true">
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-accent text-sm font-semibold text-brand-fg">2</span>
                            <span class="mt-2 w-px flex-1 bg-border"></span>
                        </div>
                        <div class="min-w-0 flex-1 pt-1">
                            <div class="flex items-start gap-2">
                                <div>
                                    <p class="font-medium leading-snug">กรอกรายละเอียดนักศึกษา</p>
                                    <p class="mt-1 text-sm leading-relaxed text-muted">ชื่อ รหัส และข้อมูลติดต่อสำหรับรับของ</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="relative flex gap-3 pb-5">
                        <div class="flex w-9 shrink-0 flex-col items-center" aria-hidden="true">
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-accent text-sm font-semibold text-brand-fg">3</span>
                            <span class="mt-2 w-px flex-1 bg-border"></span>
                        </div>
                        <div class="min-w-0 flex-1 pt-1">
                            <div class="flex items-start gap-2">
                                <div>
                                    <p class="font-medium leading-snug">เลือกรับเองหรือไปรษณีย์</p>
                                    <p class="mt-1 text-sm leading-relaxed text-muted">รับที่ศูนย์หนังสือฯ / หอประชุมฯ หรือจัดส่ง</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="relative flex gap-3">
                        <div class="flex w-9 shrink-0 flex-col items-center" aria-hidden="true">
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-accent text-sm font-semibold text-brand-fg">4</span>
                        </div>
                        <div class="min-w-0 flex-1 pt-1">
                            <div class="flex items-start gap-2">
                                <div>
                                    <p class="font-medium leading-snug">สแกนชำระเงินและแนบสลิป</p>
                                    <p class="mt-1 text-sm leading-relaxed text-muted">ชำระตามยอด แล้วอัปโหลดสลิปเพื่อยืนยัน</p>
                                </div>
                            </div>
                        </div>
                    </li>
                </ol>
            </x-storefront.card>
        @endif
    </main>

    <x-storefront.tabbar />
</div>
