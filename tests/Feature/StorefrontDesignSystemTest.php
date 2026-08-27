<?php

use Illuminate\Support\Facades\Blade;

test('storefront button variants keep touch targets and loading rules', function () {
    $primary = Blade::render('<x-storefront.button wire:click="save">ไปชำระเงิน</x-storefront.button>');

    expect($primary)
        ->toContain('min-h-11')
        ->toContain('bg-accent')
        ->toContain('text-brand-fg')
        ->toContain('wire:loading.attr="disabled"')
        ->toContain('wire:target="save"')
        ->toContain('wire:click="save"')
        ->toContain('animate-spin');

    $link = Blade::render('<x-storefront.button href="/checkout" block>ดำเนินการจอง</x-storefront.button>');

    expect($link)
        ->toContain('href="/checkout"')
        ->toContain('wire:navigate')
        ->toContain('w-full')
        ->toContain('min-h-11')
        ->not->toContain('wire:loading');

    $secondary = Blade::render('<x-storefront.button variant="secondary">ไปหน้าหลัก</x-storefront.button>');

    expect($secondary)
        ->toContain('border-border')
        ->toContain('min-h-11')
        ->not->toContain('animate-spin');

    $ghost = Blade::render('<x-storefront.button variant="ghost" aria-label="กลับหน้าหลัก">x</x-storefront.button>');

    expect($ghost)
        ->toContain('min-h-11')
        ->toContain('min-w-11')
        ->toContain('aria-label="กลับหน้าหลัก"');

    $quiet = Blade::render('<x-storefront.button variant="quiet" wire:click="remove">ลบรายการ</x-storefront.button>');

    expect($quiet)
        ->toContain('text-muted')
        ->toContain('wire:loading.attr="disabled"')
        ->toContain('disabled:opacity-60')
        ->not->toContain('animate-spin');
});

test('storefront card price badge chip empty state and bottom bar render expected classes', function () {
    expect(Blade::render('<x-storefront.card>เนื้อหา</x-storefront.card>'))
        ->toContain('rounded-brand')
        ->toContain('border-border')
        ->toContain('bg-surface')
        ->toContain('p-4')
        ->toContain('เนื้อหา');

    expect(Blade::render('<x-storefront.price :amount="$amount" size="lg" />', ['amount' => 350]))
        ->toContain('฿350.00')
        ->toContain('text-brand')
        ->toContain('text-lg');

    expect(Blade::render('<x-storefront.badge>ซื้อแยกได้</x-storefront.badge>'))
        ->toContain('rounded-full')
        ->toContain('bg-bg')
        ->toContain('ซื้อแยกได้');

    expect(Blade::render('<x-storefront.badge variant="highlight">โปร</x-storefront.badge>'))
        ->toContain('bg-highlight-soft')
        ->toContain('text-highlight-fg');

    $selected = Blade::render('<x-storefront.chip :selected="true" wire:click="selectOption(\'size\', \'M\')">M</x-storefront.chip>');

    expect($selected)
        ->toContain('aria-pressed="true"')
        ->toContain('bg-accent')
        ->toContain('min-h-11')
        ->toContain('wire:loading.attr="disabled"')
        ->not->toContain('animate-spin');

    expect(Blade::render('<x-storefront.empty-state icon="shopping-cart" description="ยังไม่มีสินค้าในตะกร้า" />'))
        ->toContain('role="status"')
        ->toContain('ยังไม่มีสินค้าในตะกร้า')
        ->toContain('<svg');

    expect(Blade::render('<x-storefront.bottom-bar>ดำเนินการจอง</x-storefront.bottom-bar>'))
        ->toContain('fixed')
        ->toContain('bottom-14')
        ->toContain('max-w-lg')
        ->toContain('ดำเนินการจอง');
});

test('storefront form primitives render labels errors and step states', function () {
    expect(Blade::render('<x-storefront.input id="student_id" wire:model="student_id" />'))
        ->toContain('min-h-11')
        ->toContain('focus:ring-accent')
        ->toContain('rounded-brand')
        ->toContain('wire:model="student_id"');

    expect(Blade::render('<x-storefront.input variant="pill" id="header-search" placeholder="ค้นหา" />'))
        ->toContain('rounded-full')
        ->toContain('border-0')
        ->toContain('bg-surface')
        ->toContain('ps-10')
        ->not->toContain('rounded-brand')
        ->not->toContain('mt-1');

    $select = Blade::render(
        '<x-storefront.select id="faculty" placeholder="เลือกคณะ" :options="$options" wire:model="faculty" />',
        ['options' => ['คณะวิทยาศาสตร์และเทคโนโลยี']],
    );

    expect($select)
        ->not->toContain('<select')
        ->toContain('role="combobox"')
        ->toContain('role="listbox"')
        ->toContain('type="hidden"')
        ->toContain('wire:model="faculty"')
        ->toContain('min-h-11')
        ->toContain('bg-surface')
        ->toContain('เลือกคณะ')
        ->toContain('คณะวิทยาศาสตร์และเทคโนโลยี');

    expect(Blade::render('<x-storefront.radio-card title="จ่ายเต็ม" caption="฿350.00" value="full" wire:model.live="payment_mode" />'))
        ->toContain('type="radio"')
        ->toContain('จ่ายเต็ม')
        ->toContain('฿350.00')
        ->toContain('wire:model.live="payment_mode"');

    $checkout = Blade::render('<x-storefront.step-bar :steps="$steps" current="ข้อมูล" />', [
        'steps' => ['ตะกร้า', 'ข้อมูล', 'ชำระเงิน', 'เสร็จสิ้น'],
    ]);

    expect($checkout)
        ->toContain('aria-label="ขั้นตอนการจอง"')
        ->toContain('aria-current="step"')
        ->toContain('border-highlight');

    $order = Blade::render('<x-storefront.step-bar variant="order" :steps="$steps" label="สถานะคำสั่งซื้อ" />', [
        'steps' => [
            ['label' => 'จองแล้ว', 'state' => 'done'],
            ['label' => 'ตรวจสลิป', 'state' => 'current'],
            ['label' => 'พร้อมรับ', 'state' => 'upcoming'],
            ['label' => 'รับแล้ว', 'state' => 'upcoming'],
        ],
    ]);

    expect($order)
        ->toContain('aria-label="สถานะคำสั่งซื้อ"')
        ->toContain('bg-accent/15')
        ->toContain('bg-border/50');

    $empty = Blade::render('<x-storefront.slip-dropzone />');

    expect($empty)
        ->toContain('type="file"')
        ->toContain('sr-only')
        ->toContain('min-h-36')
        ->toContain('border-accent')
        ->toContain('แนบสลิปการโอน')
        ->toContain('เลือกไฟล์')
        ->toContain('aria-describedby="slip-hint"')
        ->toContain('x-on:drop.prevent')
        ->toContain('กำลังอัปโหลด...')
        ->toContain('<svg');

    $attached = Blade::render('<x-storefront.slip-dropzone filename="slip.jpg" preview-url="/tmp/slip.jpg" />');

    expect($attached)
        ->toContain('แนบแล้ว')
        ->toContain('slip.jpg')
        ->toContain('src="/tmp/slip.jpg"')
        ->toContain('alt="ตัวอย่างสลิป slip.jpg"')
        ->toContain('แตะเพื่อเปลี่ยนไฟล์')
        ->not->toContain('ลากมาวางหรือแตะเพื่อเลือก')
        ->not->toContain('เลือกไฟล์');

    $pdf = Blade::render('<x-storefront.slip-dropzone filename="slip.pdf" preview-url="/tmp/slip.pdf" />');

    expect($pdf)
        ->toContain('แนบแล้ว')
        ->toContain('slip.pdf')
        ->not->toContain('src="/tmp/slip.pdf"');
});

test('storefront pages do not paste raw cta card input or palette classes', function () {
    $pages = glob(resource_path('views/pages/storefront/*.blade.php'));

    expect($pages)->not->toBeEmpty();

    foreach ($pages as $page) {
        $contents = file_get_contents($page);

        expect($contents)
            ->not->toContain('bg-accent px-4 font-medium')
            ->not->toMatch('/\bbg-gray-\d+\b/')
            ->not->toMatch('/\bbg-blue-\d+\b/');
    }
});
