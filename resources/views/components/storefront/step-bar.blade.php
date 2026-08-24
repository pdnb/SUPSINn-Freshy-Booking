@props([
    'steps',
    'current' => null,
    'variant' => 'checkout',
    'label' => 'ขั้นตอนการจอง',
])

@php
    $isOrder = $variant === 'order';
    $columnCount = count($steps);
@endphp

<ol
    {{ $attributes->class([
        'mt-4 grid text-center',
        'grid-cols-3' => $columnCount === 3,
        'grid-cols-4' => $columnCount !== 3,
        'gap-1.5 text-[11px]' => $isOrder,
        'gap-1 text-xs text-muted' => ! $isOrder,
    ]) }}
    aria-label="{{ $label }}"
>
    @foreach ($steps as $step)
        @php
            if ($isOrder) {
                $stepLabel = $step['label'];
                $state = $step['state'];
            } else {
                $stepLabel = is_array($step) ? $step['label'] : $step;
                $state = $stepLabel === $current ? 'current' : 'upcoming';
            }
        @endphp
        <li
            @class([
                'rounded-brand px-1 py-2',
                'font-medium' => $isOrder || $state === 'current',
                'bg-accent text-brand-fg' => $state === 'current',
                'bg-accent/15 text-brand' => $isOrder && $state === 'done',
                'bg-surface' => ! $isOrder && $state !== 'current',
                'bg-border/50 text-muted' => $isOrder && $state === 'upcoming',
                'border-b-2 border-highlight' => $state === 'current',
            ])
            @if ($state === 'current') aria-current="step" @endif
        >
            {{ $stepLabel }}
        </li>
    @endforeach
</ol>
