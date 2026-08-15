@php
    $pending = app(\App\Services\Order\OrderService::class)->countPendingReview();
    $items = [
        ['id' => 'overview', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'ภาพรวม', 'icon' => 'squares-2x2'],
        ['id' => 'orders', 'route' => 'admin.orders', 'match' => 'admin.orders*', 'label' => 'ออเดอร์', 'icon' => 'clipboard-document-check', 'badge' => $pending],
        ['id' => 'fulfillment', 'route' => 'admin.fulfillment', 'match' => 'admin.fulfillment', 'label' => 'จัดส่ง', 'icon' => 'truck'],
        ['id' => 'pickup', 'route' => 'admin.pickup', 'match' => 'admin.pickup', 'label' => 'รับของ', 'icon' => 'gift'],
        ['id' => 'production', 'route' => 'admin.production', 'match' => 'admin.production', 'label' => 'สรุปยอด', 'icon' => 'chart-bar'],
        ['id' => 'inventory', 'route' => 'admin.inventory', 'match' => 'admin.inventory', 'label' => 'สต็อก', 'icon' => 'cube'],
        ['id' => 'products', 'route' => 'admin.products', 'match' => 'admin.products*', 'label' => 'สินค้า', 'icon' => 'shopping-bag'],
        ['id' => 'rounds', 'route' => 'admin.rounds', 'match' => 'admin.rounds', 'label' => 'รอบจอง', 'icon' => 'calendar-days'],
        ['id' => 'settings', 'route' => 'admin.settings', 'match' => 'admin.settings', 'label' => 'ตั้งค่า', 'icon' => 'cog-6-tooth'],
    ];
@endphp

<aside class="sidebar">
    <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
        <div class="sidebar-mark">มร</div>
        <div>
            <strong>แอดมินปฏิบัติการ</strong>
            <span>ชุดเฟรชชี่ มรส.</span>
        </div>
    </a>
    <nav class="nav-group" aria-label="เมนูแอดมิน">
        @foreach ($items as $item)
            <a
                class="nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
                href="{{ route($item['route']) }}"
            >
                <x-icon :name="$item['icon']" size="sm" />
                <span>{{ $item['label'] }}</span>
                @if (($item['badge'] ?? 0) > 0)
                    <span class="nav-badge">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</aside>
