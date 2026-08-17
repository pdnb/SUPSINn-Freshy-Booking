@props([
    'title',
    'caption' => null,
])

<label {{ $attributes->except(['wire:model', 'wire:model.live', 'value', 'type', 'name'])->class('flex min-h-11 items-start gap-3 rounded-brand border border-border p-3') }}>
    <input
        type="radio"
        class="mt-1"
        {{ $attributes->only(['wire:model', 'wire:model.live', 'value', 'name']) }}
    >
    <span>
        <span class="block font-medium">{{ $title }}</span>
        @if (filled($caption))
            <span class="block text-sm text-muted">{{ $caption }}</span>
        @endif
        {{ $slot }}
    </span>
</label>
