@php
    $pending = app(\App\Services\Order\OrderService::class)->countPendingReview();
    $groups = [
        [
            'items' => [
                ['id' => 'overview', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'ภาพรวม', 'icon' => 'squares-2x2'],
                ['id' => 'orders', 'route' => 'admin.orders', 'match' => 'admin.orders*', 'label' => 'ออเดอร์', 'icon' => 'clipboard-document-check', 'badge' => $pending],
                ['id' => 'production', 'route' => 'admin.production', 'match' => 'admin.production', 'label' => 'สรุปยอด', 'icon' => 'chart-bar'],
            ],
        ],
        [
            'label' => 'แพ็คและส่ง',
            'items' => [
                ['id' => 'packing', 'route' => 'admin.packing-checklist', 'match' => 'admin.packing-checklist*', 'label' => 'แพ็คของ', 'icon' => 'printer'],
                ['id' => 'fulfillment', 'route' => 'admin.fulfillment', 'match' => 'admin.fulfillment', 'label' => 'จัดส่ง', 'icon' => 'truck'],
                ['id' => 'pickup', 'route' => 'admin.pickup', 'match' => 'admin.pickup', 'label' => 'รับของ', 'icon' => 'gift'],
            ],
        ],
        [
            'label' => 'คลัง',
            'items' => [
                ['id' => 'products', 'route' => 'admin.products', 'match' => 'admin.products*', 'label' => 'สินค้า', 'icon' => 'shopping-bag'],
                ['id' => 'inventory', 'route' => 'admin.inventory', 'match' => 'admin.inventory', 'label' => 'สต็อก', 'icon' => 'cube'],
                ['id' => 'rounds', 'route' => 'admin.rounds', 'match' => 'admin.rounds*', 'label' => 'รอบจอง', 'icon' => 'calendar-days'],
            ],
        ],
        [
            'label' => 'ระบบ',
            'items' => [
                ['id' => 'users', 'route' => 'admin.users', 'match' => 'admin.users', 'label' => 'ผู้ใช้', 'icon' => 'users'],
                ['id' => 'settings', 'route' => 'admin.settings', 'match' => 'admin.settings', 'label' => 'ตั้งค่า', 'icon' => 'cog-6-tooth'],
            ],
        ],
    ];
@endphp

<aside class="sidebar">
    <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
        <div class="sidebar-mark">SR</div>
        <div>
            <strong>Admin Console</strong>
            <span>{{ config('app.name') }}</span>
        </div>
    </a>
    <nav aria-label="เมนูแอดมิน">
        @foreach ($groups as $group)
            <div class="nav-group">
                @if ($group['label'] ?? null)
                    <div class="nav-label">{{ $group['label'] }}</div>
                @endif
                @foreach ($group['items'] as $item)
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
            </div>
        @endforeach
    </nav>
</aside>
