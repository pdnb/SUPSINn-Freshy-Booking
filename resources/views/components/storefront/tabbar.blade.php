<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-surface" aria-label="เมนูหลัก">
    <div class="mx-auto grid max-w-lg grid-cols-3">
        <a
            href="{{ route('home') }}"
            wire:navigate
            @class([
                'inline-flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs',
                'text-accent font-medium' => request()->routeIs('home'),
                'text-muted hover:text-fg' => ! request()->routeIs('home'),
            ])
            @if (request()->routeIs('home')) aria-current="page" @endif
        >
            <x-icon name="home" size="lg" />
            หน้าหลัก
        </a>
        <a
            href="{{ route('orders.index') }}"
            wire:navigate
            @class([
                'inline-flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs',
                'text-accent font-medium' => request()->routeIs('orders.index', 'orders.confirmation'),
                'text-muted hover:text-fg' => ! request()->routeIs('orders.index', 'orders.confirmation'),
            ])
            @if (request()->routeIs('orders.index', 'orders.confirmation')) aria-current="page" @endif
        >
            <x-icon name="shopping-bag" size="lg" />
            คำสั่งซื้อ
        </a>
        <a
            href="{{ route('cart') }}"
            wire:navigate
            @class([
                'inline-flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs',
                'text-accent font-medium' => request()->routeIs('cart'),
                'text-muted hover:text-fg' => ! request()->routeIs('cart'),
            ])
            @if (request()->routeIs('cart')) aria-current="page" @endif
        >
            <x-icon name="shopping-cart" size="lg" />
            ตะกร้า
        </a>
    </div>
</nav>
