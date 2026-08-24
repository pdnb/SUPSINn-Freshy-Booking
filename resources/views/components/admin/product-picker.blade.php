@props([
    'products',
    'selectedIds' => [],
    'search' => '',
    'toggleMethod' => 'toggleProduct',
    'selectAllMethod' => 'selectAllProducts',
])

@php
    $selectedIdList = array_map('intval', is_array($selectedIds) ? $selectedIds : []);
    $searchQuery = trim((string) $search);
@endphp

<div {{ $attributes->class(['product-picker']) }}>
    <div class="product-picker-toolbar">
        <div class="field">
            <label for="round-product-search">ค้นหาสินค้า</label>
            <input
                id="round-product-search"
                class="input"
                type="search"
                wire:model.live.debounce.300ms="productSearch"
                placeholder="ชื่อสินค้า"
            >
        </div>
        <button
            type="button"
            class="btn btn-ghost"
            wire:click="{{ $selectAllMethod }}"
            @if ($products->isEmpty()) disabled @endif
        >
            เลือกทั้งหมด
        </button>
    </div>

    @if ($products->isEmpty())
        <p class="product-picker-empty">
            {{ $searchQuery !== '' ? 'ไม่พบสินค้าที่ตรงกับคำค้น' : 'ยังไม่มีสินค้าในแคตตาล็อก' }}
        </p>
    @else
        <div class="product-picker-grid">
            @foreach ($products as $product)
                @php
                    $isSelected = in_array((int) $product->id, $selectedIdList, true);
                    $typeLabel = $product->type === \App\Enums\ProductType::Bundle ? 'ชุดคอมโบ' : 'ชิ้นเดียว';
                @endphp
                <button
                    type="button"
                    class="product-picker-card{{ $isSelected ? ' is-selected' : '' }}"
                    wire:key="product-card-{{ $product->id }}"
                    wire:click="{{ $toggleMethod }}({{ $product->id }})"
                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                    aria-label="{{ $isSelected ? 'เอา '.$product->name.' ออก' : 'เลือก '.$product->name }}"
                >
                    @if ($product->coverImage)
                        <img class="media-thumb lg" src="{{ $product->coverImage->url() }}" alt="{{ $product->name }}">
                    @else
                        <div class="ph-img">ไม่มีรูป</div>
                    @endif
                    <span class="product-picker-card-name">{{ $product->name }}</span>
                    <span class="pill {{ $isSelected ? 'pill-active' : 'pill-neutral' }}">
                        {{ $isSelected ? 'เลือกแล้ว' : $typeLabel }}
                    </span>
                </button>
            @endforeach
        </div>

        @if ($products->hasPages())
            <nav class="product-picker-pager" aria-label="หน้าสินค้า">
                <button
                    type="button"
                    class="btn btn-ghost"
                    wire:click="previousPage"
                    @if ($products->onFirstPage()) disabled @endif
                >
                    ก่อนหน้า
                </button>
                <span class="meta">หน้า {{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
                <button
                    type="button"
                    class="btn btn-ghost"
                    wire:click="nextPage"
                    @if (! $products->hasMorePages()) disabled @endif
                >
                    ถัดไป
                </button>
            </nav>
        @endif
    @endif
</div>
