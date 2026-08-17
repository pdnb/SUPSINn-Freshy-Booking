<div
    id="page-loading"
    wire:persist="page-loading"
    class="page-loading-overlay"
    role="status"
    aria-live="polite"
    aria-hidden="true"
>
    <div class="flex flex-col items-center gap-3">
        <x-icon name="arrow-path" size="lg" class="animate-spin text-brand" />
        <p class="text-sm font-medium text-fg">กำลังโหลด...</p>
    </div>
</div>
