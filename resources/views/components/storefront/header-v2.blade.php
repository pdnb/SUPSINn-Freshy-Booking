@props([
    'cartCount' => 0,
    'query' => '',
    'results' => [],
    'searchable' => false,
])

@php
    $logoUrl = app(\App\Services\Storefront\StorefrontLogoService::class)->url();
    $brandName = 'SRU Freshy Shop';
    $circleButton = 'inline-flex min-h-11 min-w-11 cursor-pointer items-center justify-center rounded-full bg-surface text-fg shadow-sm transition-colors duration-200 hover:bg-surface-2';
    $results = collect($results);
    $showResults = filled(trim((string) $query));
@endphp
<a
    href="#content"
    class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:min-h-11 focus:rounded-brand focus:bg-surface focus:px-4 focus:py-3 focus:text-fg"
>
    ข้ามไปเนื้อหา
</a>
<header class="rounded-b-4xl bg-linear-to-br from-brand to-highlight text-brand-fg">
    <div class="mx-auto max-w-lg px-4 pb-6 pt-4">
        <div class="flex items-center justify-between gap-2">
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="{{ $circleButton }}"
                aria-label="หน้าหลัก"
            >
                @if ($logoUrl)
                    <img
                        src="{{ $logoUrl }}"
                        alt=""
                        class="size-8 object-contain"
                    >
                @else
                    <x-icon name="home" size="md" />
                @endif
            </a>
            <h1 class="text-2xl font-bold leading-tight">{{ $brandName }}</h1>
            <a
                href="{{ route('cart') }}"
                wire:navigate
                class="relative {{ $circleButton }}"
                aria-label="ตะกร้า{{ $cartCount > 0 ? ' มี '.$cartCount.' รายการ' : '' }}"
            >
                <x-icon name="shopping-cart" size="md" />
                @if ($cartCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 min-w-5 rounded-full bg-brand px-1 text-center text-xs font-medium text-brand-fg">{{ $cartCount }}</span>
                @endif
            </a>
        </div>

        <div class="relative mt-8" role="search">
            <label for="header-v2-search" class="sr-only">ค้นหาชุด</label>
            <span class="pointer-events-none absolute inset-y-0 start-3 z-10 inline-flex items-center text-muted">
                <x-icon name="magnifying-glass" size="sm" />
            </span>
            @if ($searchable)
                <x-storefront.input
                    id="header-v2-search"
                    variant="pill"
                    type="search"
                    name="search"
                    placeholder="ค้นหาสินค้า..."
                    autocomplete="off"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="header-v2-search-results"
                    aria-expanded="{{ $showResults ? 'true' : 'false' }}"
                    wire:model.live.debounce.300ms="search"
                />
            @else
                <x-storefront.input
                    id="header-v2-search"
                    variant="pill"
                    type="search"
                    name="search"
                    placeholder="ค้นหาสินค้า..."
                    autocomplete="off"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="header-v2-search-results"
                    aria-expanded="false"
                />
            @endif
            @if ($showResults)
                <x-storefront.card
                    id="header-v2-search-results"
                    padding="0"
                    class="absolute inset-x-0 top-full z-30 mt-2 overflow-hidden"
                >
                    @if ($results->isEmpty())
                        <p class="px-4 py-3 text-sm text-muted" role="status">ไม่พบสินค้า</p>
                    @else
                        <ul role="listbox" aria-label="ผลการค้นหา">
                            @foreach ($results as $product)
                                <li wire:key="header-search-{{ $product->id }}">
                                    <a
                                        href="{{ route('products.show', $product) }}"
                                        wire:navigate
                                        role="option"
                                        class="flex min-h-11 cursor-pointer items-center justify-between gap-3 px-4 py-2 text-fg transition-colors duration-200 hover:bg-surface-2"
                                    >
                                        <span class="font-medium">{{ $product->name }}</span>
                                        <x-storefront.price :amount="$product->price" size="sm" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-storefront.card>
            @endif
        </div>
    </div>
</header>
