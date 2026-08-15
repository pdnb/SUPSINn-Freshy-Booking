@props([
    'id' => 'file-upload',
    'model' => 'uploads',
    'multiple' => true,
    'accept' => 'image/*',
    'title' => 'ลากรูปมาวางที่นี่',
    'hint' => 'หรือคลิกเพื่อเลือกไฟล์ · JPG PNG WEBP · ไม่เกิน 4MB',
])

<label
    {{ $attributes->class(['dropzone']) }}
    for="{{ $id }}"
    x-data="{ dragging: false }"
    x-on:dragenter.prevent="dragging = true"
    x-on:dragover.prevent="dragging = true"
    x-on:dragleave.prevent="dragging = false"
    x-on:drop.prevent="
        dragging = false;
        const input = $refs.input;
        const transfer = new DataTransfer();
        Array.from($event.dataTransfer.files).forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    "
    x-bind:class="{ 'is-dragging': dragging }"
>
    <input
        x-ref="input"
        id="{{ $id }}"
        class="dropzone-input"
        type="file"
        wire:model="{{ $model }}"
        @if ($multiple) multiple @endif
        accept="{{ $accept }}"
    >
    <div class="dropzone-body" wire:loading.remove wire:target="{{ $model }}">
        <x-icon name="photo" size="md" />
        <strong>{{ $title }}</strong>
        <span class="muted">{{ $hint }}</span>
    </div>
    <div class="dropzone-body" wire:loading.flex wire:target="{{ $model }}">
        <span class="muted">กำลังอัปโหลด...</span>
    </div>
</label>
