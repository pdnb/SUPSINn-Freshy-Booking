@props([
    'padding' => '4',
    'as' => 'div',
])

@php
    $paddingClass = match ((string) $padding) {
        '5' => 'p-5',
        '0', 'none' => 'p-0',
        default => 'p-4',
    };
@endphp

<{{ $as }} {{ $attributes->class(['rounded-brand border border-border bg-surface', $paddingClass]) }}>
    {{ $slot }}
</{{ $as }}>
