@props([
    'variant' => 'default',
])

@php
    $classes = match ($variant) {
        'pill' => 'min-h-11 w-full rounded-full border-0 bg-surface px-4 ps-10 text-fg placeholder:text-muted focus:ring-2 focus:ring-accent',
        default => 'mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent',
    };
@endphp

<input
    {{ $attributes->class($classes) }}
>
