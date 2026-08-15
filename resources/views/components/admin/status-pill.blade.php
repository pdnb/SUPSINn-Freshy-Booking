@props(['status'])

@php
    $status = $status instanceof \App\Enums\OrderStatus
        ? $status
        : \App\Enums\OrderStatus::from((string) $status);
@endphp

<span {{ $attributes->class(['pill', $status->pillClass()]) }}>{{ $status->label() }}</span>
