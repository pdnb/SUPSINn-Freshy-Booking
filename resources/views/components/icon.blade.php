@props([
    'name',
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-[22px]',
        'xl' => 'size-10',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $component = 'heroicon-o-'.$name;
@endphp

<x-dynamic-component
    :component="$component"
    {{ $attributes->class([$sizeClass])->merge(['aria-hidden' => 'true']) }}
/>
