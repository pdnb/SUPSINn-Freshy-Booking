@props([
    'variant' => 'muted',
])

<span
    {{ $attributes->class([
        'inline-flex w-fit rounded-full px-2 py-0.5 text-xs',
        'bg-bg text-muted' => $variant === 'muted',
        'bg-highlight-soft text-highlight-fg' => $variant === 'highlight',
    ]) }}
>
    {{ $slot }}
</span>
