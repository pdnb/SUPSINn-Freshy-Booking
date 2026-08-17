@props([
    'open' => false,
    'title',
    'titleId' => 'confirm-dialog-title',
    'close',
    'confirm',
    'cancelLabel' => 'ไม่ใช่',
    'confirmLabel' => 'ยืนยันลบ',
])

<div class="overlay {{ $open ? 'is-open' : '' }}" wire:click="{{ $close }}"></div>
<div class="dialog {{ $open ? 'is-open' : '' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}">
    <div class="dialog-head">
        <h2 id="{{ $titleId }}">{{ $title }}</h2>
        <button type="button" class="icon-btn" wire:click="{{ $close }}" aria-label="ปิด">
            <x-icon name="x-mark" size="sm" />
        </button>
    </div>
    <div class="dialog-body">
        <p>{{ $slot }}</p>
    </div>
    <div class="dialog-foot">
        <button type="button" class="btn btn-secondary" wire:click="{{ $close }}">{{ $cancelLabel }}</button>
        <button type="button" class="btn btn-danger" wire:click="{{ $confirm }}">{{ $confirmLabel }}</button>
    </div>
</div>
