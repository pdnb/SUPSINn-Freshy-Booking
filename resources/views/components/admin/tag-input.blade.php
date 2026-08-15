@props([
    'tags' => [],
    'addMethod',
    'addArgs' => [],
    'removeMethod',
    'removeArgs' => [],
    'placeholder' => 'พิมพ์แล้วกด Enter',
])

@php
    $addArgList = collect($addArgs)
        ->map(fn (mixed $arg): string => is_int($arg) || is_float($arg) ? (string) $arg : \Illuminate\Support\Js::from($arg))
        ->implode(', ');
    $addCallPrefix = $addArgList === '' ? '' : $addArgList.', ';
    $removeArgList = collect($removeArgs)
        ->map(fn (mixed $arg): string => is_int($arg) || is_float($arg) ? (string) $arg : \Illuminate\Support\Js::from($arg))
        ->implode(', ');
    $removeCallPrefix = $removeArgList === '' ? '' : $removeArgList.', ';
@endphp

<div
    {{ $attributes->class(['tag-input']) }}
    x-data="{ draft: '' }"
    x-on:click="$refs.draft.focus()"
>
    @foreach ($tags as $valueIndex => $tag)
        <span class="tag-chip" wire:key="tag-{{ $valueIndex }}-{{ $tag }}">
            <span>{{ $tag }}</span>
            <button
                type="button"
                class="tag-chip-remove"
                wire:click="{{ $removeMethod }}({{ $removeCallPrefix }}{{ $valueIndex }})"
                aria-label="ลบ {{ $tag }}"
            >
                <x-icon name="x-mark" size="sm" />
            </button>
        </span>
    @endforeach
    <input
        x-ref="draft"
        type="text"
        class="tag-input-field"
        x-model="draft"
        placeholder="{{ $placeholder }}"
        x-on:keydown.enter.prevent="
            if (! draft.trim()) { return }
            $wire.{{ $addMethod }}({{ $addCallPrefix }}draft);
            draft = '';
        "
        x-on:keydown.comma.prevent="
            if (! draft.trim()) { return }
            $wire.{{ $addMethod }}({{ $addCallPrefix }}draft);
            draft = '';
        "
        x-on:blur="
            if (! draft.trim()) { return }
            $wire.{{ $addMethod }}({{ $addCallPrefix }}draft);
            draft = '';
        "
    >
</div>
