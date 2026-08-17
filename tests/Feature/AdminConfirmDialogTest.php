<?php

use Illuminate\Support\Facades\Blade;

test('confirm dialog renders open markup with methods and slot html', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin.confirm-dialog
            :open="true"
            title="ลบแบนเนอร์"
            title-id="delete-banner-title"
            close="closeDeleteBanner"
            confirm="confirmDeleteBanner"
        >
            ต้องการลบแบนเนอร์นี้หรือไม่? <span class="mono">BNR-1</span>
        </x-admin.confirm-dialog>
    BLADE);

    expect($html)
        ->toContain('class="overlay is-open"')
        ->toContain('class="dialog is-open"')
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('aria-labelledby="delete-banner-title"')
        ->toContain('id="delete-banner-title"')
        ->toContain('ลบแบนเนอร์')
        ->toContain('wire:click="closeDeleteBanner"')
        ->toContain('wire:click="confirmDeleteBanner"')
        ->toContain('ต้องการลบแบนเนอร์นี้หรือไม่?')
        ->toContain('<span class="mono">BNR-1</span>')
        ->toContain('ไม่ใช่')
        ->toContain('ยืนยันลบ');
});

test('confirm dialog stays in the dom when closed without the open class', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin.confirm-dialog
            :open="false"
            title="ลบโลโก้"
            title-id="clear-logo-title"
            close="closeClearLogo"
            confirm="confirmClearLogo"
        >
            ต้องการลบโลโก้หรือไม่?
        </x-admin.confirm-dialog>
    BLADE);

    expect($html)
        ->toContain('class="overlay')
        ->toContain('class="dialog')
        ->toContain('role="dialog"')
        ->not->toContain('is-open');
});

test('confirm dialog uses a custom confirm label', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin.confirm-dialog
            :open="true"
            title="ยกเลิกออเดอร์"
            title-id="cancel-order-title"
            close="closeCancelConfirm"
            confirm="cancel"
            confirm-label="ยืนยันยกเลิก"
        >
            ต้องการยกเลิกออเดอร์หรือไม่?
        </x-admin.confirm-dialog>
    BLADE);

    expect($html)
        ->toContain('wire:click="cancel"')
        ->toContain('ยืนยันยกเลิก')
        ->not->toContain('ยืนยันลบ');
});
