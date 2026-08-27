@props([
    'title' => 'แนบสลิปการโอน',
    'hint' => 'ลากมาวางหรือแตะเพื่อเลือก · รูปภาพหรือ PDF ไม่เกิน 5MB',
    'filename' => null,
    'previewUrl' => null,
    'describedBy' => 'slip-hint',
])

@php
    $hasFile = filled($filename);
    $isPdf = $hasFile && str_ends_with(mb_strtolower((string) $filename), '.pdf');
    $showPreview = $hasFile && filled($previewUrl) && ! $isPdf;
    $wireTarget = (string) ($attributes->get('wire:model') ?? $attributes->get('wire:model.live') ?? 'slip');
    $inputId = (string) ($attributes->get('id') ?? 'slip-file');
    $isInvalid = isset($errors) && $errors->has($wireTarget);
    $inputAttributes = $attributes->only(['wire:model', 'wire:model.live', 'name', 'required'])->merge([
        'id' => $inputId,
        'accept' => $attributes->get('accept') ?? 'image/*,.pdf',
        'class' => 'sr-only focus-visible:outline-none',
    ]);
@endphp

<label
    for="{{ $inputId }}"
    x-data="{ dragging: false }"
    x-on:dragenter.prevent="dragging = true"
    x-on:dragover.prevent="dragging = true"
    x-on:dragleave.prevent="dragging = false"
    x-on:drop.prevent="
        dragging = false;
        const file = $event.dataTransfer.files[0];
        if (! file) { return; }
        const transfer = new DataTransfer();
        transfer.items.add(file);
        const input = $refs.input;
        input.files = transfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    "
    x-bind:class="{ 'ring-2 ring-accent bg-accent/10': dragging }"
    {{ $attributes->except(['wire:model', 'wire:model.live', 'accept', 'id', 'name', 'required'])->class([
        'flex min-h-36 cursor-pointer flex-col justify-center rounded-brand border-2 px-4 py-5 transition-colors duration-200',
        'focus-within:ring-2 focus-within:ring-accent',
        'border-danger bg-surface' => $isInvalid,
        'border-border bg-surface' => $hasFile && ! $isInvalid,
        'border-dashed border-accent bg-surface-2 hover:bg-accent/10' => ! $hasFile && ! $isInvalid,
        'border-dashed bg-surface-2' => ! $hasFile && $isInvalid,
    ]) }}
>
    <input
        x-ref="input"
        type="file"
        {{ $inputAttributes }}
        @if (filled($describedBy)) aria-describedby="{{ $describedBy }}" @endif
        @if ($isInvalid) aria-invalid="true" @endif
    >

    <div wire:loading.remove wire:target="{{ $wireTarget }}" class="flex flex-col justify-center">
        @if ($hasFile)
            <span class="flex items-center gap-3">
                @if ($showPreview)
                    <img
                        src="{{ $previewUrl }}"
                        alt="ตัวอย่างสลิป {{ $filename }}"
                        class="size-16 shrink-0 rounded-brand-sm border border-border bg-white object-contain"
                    >
                @else
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-brand-sm bg-surface-2 text-success">
                        <x-icon :name="$isPdf ? 'document-text' : 'check-circle'" size="lg" />
                    </span>
                @endif
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-1.5 font-medium text-success">
                        <x-icon name="check-circle" size="sm" class="shrink-0" />
                        แนบสลิปแล้ว
                    </span>
                    <span class="mt-0.5 block truncate text-sm">{{ $filename }}</span>
                    <span class="mt-0.5 block text-sm text-muted">แตะเพื่อเปลี่ยนไฟล์</span>
                </span>
            </span>
        @else
            <span class="flex flex-col items-center gap-2 text-center">
                <span class="flex size-14 items-center justify-center rounded-full bg-accent/15 text-accent">
                    <x-icon name="arrow-up-tray" size="lg" />
                </span>
                <span class="font-medium">{{ $title }}</span>
                <span class="text-sm text-muted">{{ $hint }}</span>
            </span>
        @endif
    </div>

    <div
        wire:loading.flex
        wire:target="{{ $wireTarget }}"
        class="hidden items-center justify-center gap-2 text-sm text-muted"
        role="status"
    >
        <x-icon name="arrow-path" size="sm" class="animate-spin" />
        กำลังอัปโหลด...
    </div>
</label>
