@props([
    'label' => null,
])

@php
    $text = $label ?? trim((string) $slot);
@endphp

<label {{ $attributes->only('class')->class(['switch']) }}>
    <input
        type="checkbox"
        {{ $attributes->except('class')->class(['switch-input']) }}
    >
    <span class="switch-track" aria-hidden="true"></span>
    @if ($text !== '')
        <span class="switch-label">{{ $text }}</span>
    @endif
</label>
