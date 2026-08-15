<a
    href="#content"
    class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:min-h-11 focus:rounded-brand focus:bg-surface focus:px-4 focus:py-3 focus:text-fg"
>
    ข้ามไปเนื้อหา
</a>
<header class="bg-brand text-brand-fg">
    <div class="mx-auto flex min-h-[58px] max-w-lg items-center justify-between gap-3 px-4">
        <a href="{{ route('home') }}" class="min-h-11 inline-flex items-center text-base font-semibold hover:opacity-90">
            มรส. ชุดเฟรชชี่
        </a>
        <a
            href="{{ route('cart') }}"
            class="relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-brand-press"
            aria-label="ตะกร้า{{ $cartCount > 0 ? ' มี '.$cartCount.' รายการ' : '' }}"
        >
            <x-icon name="shopping-cart" size="md" />
            @if ($cartCount > 0)
                <span class="absolute right-0 top-1 min-w-5 rounded-full bg-accent-fg px-1 text-center text-xs font-medium text-accent">{{ $cartCount }}</span>
            @endif
        </a>
    </div>
</header>
