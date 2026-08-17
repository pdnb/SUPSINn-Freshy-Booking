@props([
    'title' => 'แนบสลิปการโอน',
    'hint' => 'รูปภาพหรือ PDF ไม่เกิน 5MB',
    'filename' => null,
    'describedBy' => 'slip-hint',
])

<label class="mt-2 flex min-h-24 cursor-pointer flex-col justify-center rounded-brand border border-dashed border-border bg-surface px-4 py-4">
    <span class="font-medium">{{ $title }}</span>
    <span class="text-sm text-muted">{{ $hint }}</span>
    <input
        type="file"
        {{ $attributes->merge([
            'class' => 'mt-3 text-sm',
            'accept' => 'image/*,.pdf',
        ]) }}
        @if (filled($describedBy)) aria-describedby="{{ $describedBy }}" @endif
    >
    @if (filled($filename))
        <span class="mt-2 text-sm text-success">แนบแล้ว: {{ $filename }}</span>
    @endif
</label>
