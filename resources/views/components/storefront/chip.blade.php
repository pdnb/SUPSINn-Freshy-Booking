@props([
    'selected' => false,
])

<button
    type="button"
    wire:loading.attr="disabled"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    {{ $attributes->class([
        'inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand border px-3 text-sm disabled:cursor-not-allowed disabled:opacity-60',
        'border-accent bg-accent text-brand-fg' => $selected,
        'border-border bg-surface hover:border-accent' => ! $selected,
    ]) }}
>
    {{ $slot }}
</button>
