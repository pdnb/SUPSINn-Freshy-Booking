@props([
    'icon',
    'title' => null,
    'description' => null,
    'kicker' => null,
])

<div {{ $attributes->class('flex flex-col items-center text-center') }} role="status">
    <x-icon :name="$icon" size="xl" class="text-muted" />

    @if (filled($kicker))
        <p class="mt-4 text-sm font-medium text-brand">{{ $kicker }}</p>
    @endif

    @if (filled($title))
        <h2 @class(['text-xl font-semibold', 'mt-2' => filled($kicker), 'mt-4' => ! filled($kicker)])>{{ $title }}</h2>
    @endif

    @if (filled($description))
        <p @class(['text-sm text-muted', 'mt-2' => filled($kicker) || filled($title), 'mt-4' => ! filled($kicker) && ! filled($title)])>{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
