@props([
    'amount',
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'sm' => 'text-sm',
        'lg' => 'text-lg font-medium',
        default => 'font-semibold',
    };
@endphp

<span {{ $attributes->class([$sizeClass, 'text-brand']) }}>฿{{ number_format((float) $amount, 2) }}</span>
