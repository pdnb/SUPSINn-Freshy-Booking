@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'target' => null,
    'block' => false,
    'type' => 'button',
])

@php
    $wireTarget = $target ?? $attributes->get('wire:target') ?? $attributes->get('wire:click');
    $attributes = $attributes->except('wire:target');
    $isLink = filled($href);
    $showSpinner = ! $isLink && $variant === 'primary' && filled($wireTarget);

    $isIcon = $size === 'icon' || $variant === 'ghost';
    $classes = [
        'inline-flex items-center justify-center disabled:cursor-not-allowed disabled:opacity-60',
        'w-full' => $block,
        'gap-2' => $showSpinner,
        'min-h-11 min-w-11 rounded-brand' => $isIcon,
        'min-h-11 rounded-brand px-4 font-medium' => ! $isIcon && $variant !== 'quiet',
        'min-h-11 rounded-brand' => $variant === 'quiet',
        'bg-accent text-brand-fg hover:bg-accent-press' => $variant === 'primary',
        'border border-border bg-surface hover:border-accent hover:bg-bg' => $variant === 'secondary',
        'hover:bg-surface-2' => $variant === 'ghost',
        'text-sm text-muted hover:text-fg' => $variant === 'quiet',
    ];
@endphp

@if ($isLink)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @if (filled($wireTarget))
            wire:loading.attr="disabled"
            wire:target="{!! htmlspecialchars((string) $wireTarget, ENT_COMPAT, 'UTF-8') !!}"
        @endif
        {{ $attributes->class($classes) }}
    >
        @if ($showSpinner)
            <span wire:loading.remove wire:target="{!! htmlspecialchars((string) $wireTarget, ENT_COMPAT, 'UTF-8') !!}">{{ $slot }}</span>
            <span wire:loading wire:target="{!! htmlspecialchars((string) $wireTarget, ENT_COMPAT, 'UTF-8') !!}" class="inline-flex items-center gap-2">
                <x-icon name="arrow-path" size="sm" class="animate-spin" />
            </span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
