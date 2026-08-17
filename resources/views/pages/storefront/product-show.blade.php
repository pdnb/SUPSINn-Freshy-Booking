<?php

use App\Models\Product;
use App\Services\Booking\BookingRoundService;
use App\Services\Cart\CartService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('รายละเอียดสินค้า')] class extends Component
{
    public Product $product;

    /** @var array<string, string> */
    public array $options = [];

    /** @var array<int, array<string, string>> */
    public array $componentOptions = [];

    public function mount(Product $product, BookingRoundService $booking): void
    {
        if (! $product->is_active || ! $booking->isProductAvailable($product)) {
            abort(404);
        }

        $this->product = $product->load(['optionGroups.values', 'components.optionGroups.values', 'images']);
    }

    public function selectOption(string $key, string $value): void
    {
        $this->options[$key] = $value;
    }

    public function selectComponentOption(int $componentId, string $key, string $value): void
    {
        $this->componentOptions[$componentId][$key] = $value;
    }

    public function addToCart(CartService $cart): void
    {
        try {
            $cart->add($this->product, [
                'options' => $this->options,
                'components' => $this->componentOptions,
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('storefront-toast', message: $exception->validator->errors()->first());

            return;
        }

        $this->redirect(route('cart'), navigate: true);
    }

    public function render(CartService $cart)
    {
        return $this->view([
            'cartCount' => $cart->count(),
        ]);
    }
};
?>

<div class="min-h-dvh bg-bg pb-40 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg">
        @if ($product->images->isNotEmpty())
            <section
                class="touch-pan-y overflow-hidden bg-bg"
                data-od-id="product-gallery"
                wire:ignore
                aria-roledescription="carousel"
                aria-label="รูปสินค้า"
                @if ($product->images->count() > 1) tabindex="0" @endif
                x-data="{
                    index: 0,
                    count: {{ $product->images->count() }},
                    startX: null,
                    next() { this.index = (this.index + 1) % this.count },
                    prev() { this.index = (this.index - 1 + this.count) % this.count },
                    onPointerDown(event) {
                        this.startX = event.clientX;
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

                        deltaX < 0 ? this.next() : this.prev();
                    },
                }"
                @if ($product->images->count() > 1)
                    x-on:pointerdown="onPointerDown($event)"
                    x-on:pointerup="onPointerUp($event)"
                    x-on:pointercancel="startX = null"
                    x-on:keydown.left.prevent="prev()"
                    x-on:keydown.right.prevent="next()"
                @endif
            >
                <div class="relative aspect-[16/9]">
                    @foreach ($product->images as $index => $image)
                        <img
                            src="{{ $image->url() }}"
                            alt="{{ $product->name }}"
                            wire:key="product-gallery-{{ $image->id }}"
                            x-show="index === {{ $index }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            x-cloak
                            draggable="false"
                            class="absolute inset-0 h-full w-full object-cover"
                        >
                    @endforeach
                    @if ($product->images->count() > 1)
                        <button type="button" class="absolute left-2 top-1/2 z-10 inline-flex size-11 -translate-y-1/2 items-center justify-center" x-on:click="prev()" x-on:pointerdown.stop x-on:pointerup.stop aria-label="รูปก่อนหน้า">
                            <x-icon name="chevron-left" size="md" />
                        </button>
                        <button type="button" class="absolute right-2 top-1/2 z-10 inline-flex size-11 -translate-y-1/2 items-center justify-center" x-on:click="next()" x-on:pointerdown.stop x-on:pointerup.stop aria-label="รูปถัดไป">
                            <x-icon name="chevron-right" size="md" />
                        </button>
                        <p class="absolute bottom-2 right-2 z-10 rounded-full bg-surface/80 px-2 py-0.5 text-xs text-muted" aria-hidden="true">
                            <span x-text="index + 1"></span>/{{ $product->images->count() }}
                        </p>
                    @endif
                </div>
            </section>
        @else
            <div class="flex aspect-[4/3] items-center justify-center bg-bg text-muted" aria-hidden="true">
                {{ $product->type === \App\Enums\ProductType::Bundle ? 'ชุด' : 'สินค้า' }}
            </div>
        @endif

        <section class="border-b border-border bg-surface px-4 py-5">
            <x-storefront.badge>
                {{ $product->type === \App\Enums\ProductType::Bundle ? 'คอมโบ · ไม่ขายแยก' : 'ซื้อแยกได้' }}
            </x-storefront.badge>
            <h2 class="mt-2 text-xl font-semibold">{{ $product->name }}</h2>
            <x-storefront.price :amount="$product->price" size="lg" class="mt-1" />
            @if ($product->description)
                <p class="mt-3 text-sm text-muted">{{ $product->description }}</p>
            @elseif ($product->type === \App\Enums\ProductType::Bundle)
                <p class="mt-3 text-sm text-muted">ชุดนี้บังคับซื้อครบทุกชิ้น กรุณาเลือกตัวเลือกให้ครบก่อนเพิ่มตะกร้า</p>
            @endif
        </section>

        @if ($product->type === \App\Enums\ProductType::Simple)
            @foreach ($product->optionGroups as $group)
                <section class="border-b border-border bg-surface px-4 py-5" wire:key="group-{{ $group->id }}">
                    <h3 class="font-semibold">{{ $group->label }}</h3>
                    <p class="mt-1 text-xs text-muted">จำเป็น</p>
                    <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="{{ $group->label }}">
                        @foreach ($group->values as $value)
                            <x-storefront.chip
                                wire:key="option-{{ $group->id }}-{{ $value->id }}"
                                wire:click="selectOption('{{ $group->key }}', '{{ $value->value }}')"
                                wire:target="selectOption('{{ $group->key }}', '{{ $value->value }}')"
                                :selected="($options[$group->key] ?? null) === $value->value"
                            >
                                {{ $value->value }}
                            </x-storefront.chip>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @else
            @foreach ($product->components as $index => $bundleComponent)
                <section class="border-b border-border bg-surface px-4 py-5" wire:key="component-{{ $bundleComponent->id }}">
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-semibold">{{ $index + 1 }}. {{ $bundleComponent->name }}</h3>
                        <span class="text-xs text-muted">จำเป็น</span>
                    </div>
                    @foreach ($bundleComponent->optionGroups as $group)
                        <div class="mt-4" wire:key="component-group-{{ $group->id }}">
                            <p class="text-sm font-medium" id="label-{{ $group->id }}">{{ $group->label }}</p>
                            <div class="mt-2 flex flex-wrap gap-2" role="group" aria-labelledby="label-{{ $group->id }}">
                                @foreach ($group->values as $value)
                                    <x-storefront.chip
                                        wire:key="component-option-{{ $group->id }}-{{ $value->id }}"
                                        wire:click="selectComponentOption({{ $bundleComponent->id }}, '{{ $group->key }}', '{{ $value->value }}')"
                                        wire:target="selectComponentOption({{ $bundleComponent->id }}, '{{ $group->key }}', '{{ $value->value }}')"
                                        :selected="($componentOptions[$bundleComponent->id][$group->key] ?? null) === $value->value"
                                    >
                                        {{ $value->value }}
                                    </x-storefront.chip>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endforeach
        @endif
    </main>

    <x-storefront.bottom-bar class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs text-muted">{{ $product->type === \App\Enums\ProductType::Bundle ? 'รวมทั้งชุด' : 'ราคา' }}</p>
            <x-storefront.price :amount="$product->price" />
        </div>
        <x-storefront.button wire:click="addToCart">ใส่ตะกร้า</x-storefront.button>
    </x-storefront.bottom-bar>

    <x-storefront.tabbar />
</div>
