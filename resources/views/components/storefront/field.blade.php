@props([
    'label',
    'name',
    'for' => null,
])

@php
    $inputId = $for ?? $name;
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-sm font-medium">{{ $label }}</label>
    {{ $slot }}
    @error($name)
        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
    @enderror
</div>
