<?php

use App\Models\Product;
use App\Services\Booking\BookingRoundService;
use App\Services\Cart\CartService;
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
        $cart->add($this->product, [
            'options' => $this->options,
            'components' => $this->componentOptions,
        ]);

        $this->redirect(route('cart'));
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
        <div class="flex items-center gap-2 border-b border-border bg-surface px-2">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-bg" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </a>
            <h1 class="text-base font-semibold">รายละเอียดสินค้า</h1>
        </div>

        @if ($product->images->isNotEmpty())
            <div
                class="relative overflow-hidden bg-bg"
                x-data="{
                    index: 0,
                    count: {{ $product->images->count() }},
                    next() { this.index = (this.index + 1) % this.count },
                    prev() { this.index = (this.index - 1 + this.count) % this.count },
                }"
            >
                @foreach ($product->images as $index => $image)
                    <img
                        src="{{ $image->url() }}"
                        alt="{{ $product->name }}"
                        wire:key="product-gallery-{{ $image->id }}"
                        x-show="index === {{ $index }}"
                        x-cloak
                        class="aspect-[4/3] w-full object-cover"
                    >
                @endforeach
                @if ($product->images->count() > 1)
                    <button type="button" class="absolute left-2 top-1/2 inline-flex size-11 -translate-y-1/2 items-center justify-center rounded-brand bg-surface/80" x-on:click="prev()" aria-label="รูปก่อนหน้า">
                        <x-icon name="chevron-left" size="md" />
                    </button>
                    <button type="button" class="absolute right-2 top-1/2 inline-flex size-11 -translate-y-1/2 items-center justify-center rounded-brand bg-surface/80" x-on:click="next()" aria-label="รูปถัดไป">
                        <x-icon name="chevron-right" size="md" />
                    </button>
                    <p class="absolute bottom-2 right-2 rounded-full bg-surface/80 px-2 py-0.5 text-xs text-muted" aria-hidden="true">
                        <span x-text="index + 1"></span>/{{ $product->images->count() }}
                    </p>
                @endif
            </div>
        @else
            <div class="flex aspect-[4/3] items-center justify-center bg-bg text-muted" aria-hidden="true">
                {{ $product->type === \App\Enums\ProductType::Bundle ? 'ชุด' : 'สินค้า' }}
            </div>
        @endif

        <section class="border-b border-border bg-surface px-4 py-5">
            <span class="inline-flex rounded-full bg-bg px-2 py-0.5 text-xs text-muted">
                {{ $product->type === \App\Enums\ProductType::Bundle ? 'คอมโบ · ไม่ขายแยก' : 'ซื้อแยกได้' }}
            </span>
            <h2 class="mt-2 text-xl font-semibold">{{ $product->name }}</h2>
            <p class="mt-1 text-lg font-medium">฿{{ number_format((float) $product->price, 2) }}</p>
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
                            <button
                                type="button"
                                wire:key="option-{{ $group->id }}-{{ $value->id }}"
                                wire:click="selectOption('{{ $group->key }}', '{{ $value->value }}')"
                                wire:loading.attr="disabled"
                                wire:target="selectOption('{{ $group->key }}', '{{ $value->value }}')"
                                aria-pressed="{{ ($options[$group->key] ?? null) === $value->value ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand border px-3 text-sm disabled:cursor-not-allowed disabled:opacity-60',
                                    'border-accent bg-accent text-accent-fg' => ($options[$group->key] ?? null) === $value->value,
                                    'border-border bg-surface hover:border-accent' => ($options[$group->key] ?? null) !== $value->value,
                                ])
                            >
                                {{ $value->value }}
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
            @error('options') <p class="px-4 py-3 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        @else
            @foreach ($product->components as $index => $component)
                <section class="border-b border-border bg-surface px-4 py-5" wire:key="component-{{ $component->id }}">
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-semibold">{{ $index + 1 }}. {{ $component->name }}</h3>
                        <span class="text-xs text-muted">จำเป็น</span>
                    </div>
                    @foreach ($component->optionGroups as $group)
                        <div class="mt-4" wire:key="component-group-{{ $group->id }}">
                            <p class="text-sm font-medium" id="label-{{ $group->id }}">{{ $group->label }}</p>
                            <div class="mt-2 flex flex-wrap gap-2" role="group" aria-labelledby="label-{{ $group->id }}">
                                @foreach ($group->values as $value)
                                    <button
                                        type="button"
                                        wire:key="component-option-{{ $group->id }}-{{ $value->id }}"
                                        wire:click="selectComponentOption({{ $component->id }}, '{{ $group->key }}', '{{ $value->value }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="selectComponentOption({{ $component->id }}, '{{ $group->key }}', '{{ $value->value }}')"
                                        aria-pressed="{{ ($componentOptions[$component->id][$group->key] ?? null) === $value->value ? 'true' : 'false' }}"
                                        @class([
                                            'inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand border px-3 text-sm disabled:cursor-not-allowed disabled:opacity-60',
                                            'border-accent bg-accent text-accent-fg' => ($componentOptions[$component->id][$group->key] ?? null) === $value->value,
                                            'border-border bg-surface hover:border-accent' => ($componentOptions[$component->id][$group->key] ?? null) !== $value->value,
                                        ])
                                    >
                                        {{ $value->value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endforeach
            @error('components') <p class="px-4 py-3 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        @endif

        @error('product') <p class="px-4 py-3 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
    </main>

    <div class="fixed inset-x-0 bottom-14 z-10 border-t border-border bg-surface">
        <div class="mx-auto flex max-w-lg items-center justify-between gap-3 px-4 py-3">
            <div>
                <p class="text-xs text-muted">{{ $product->type === \App\Enums\ProductType::Bundle ? 'รวมทั้งชุด' : 'ราคา' }}</p>
                <p class="font-semibold">฿{{ number_format((float) $product->price, 2) }}</p>
            </div>
            <button
                type="button"
                wire:click="addToCart"
                wire:loading.attr="disabled"
                wire:target="addToCart"
                class="inline-flex min-h-11 items-center gap-2 rounded-brand bg-accent px-4 font-medium text-accent-fg hover:bg-accent-press disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="addToCart">ใส่ตะกร้า</span>
                <span wire:loading wire:target="addToCart" class="inline-flex items-center gap-2">
                    <x-icon name="arrow-path" size="sm" class="animate-spin" />
                </span>
            </button>
        </div>
    </div>

    <x-storefront.tabbar />
</div>
