<?php

use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Order\OrderService;
use App\Services\Payment\PromptPayQrService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('ชำระเงิน')] class extends Component
{
    use WithFileUploads;

    public mixed $slip = null;

    public function mount(CartService $cart, CheckoutService $checkout): void
    {
        if ($cart->items()->isEmpty()) {
            $this->redirect(route('cart'));

            return;
        }

        if ($checkout->draft() === null) {
            $this->redirect(route('checkout'));
        }
    }

    public function confirm(OrderService $orders): void
    {
        $this->validate([
            'slip' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'slip.required' => 'กรุณาแนบสลิป',
        ]);

        /** @var TemporaryUploadedFile $slip */
        $slip = $this->slip;

        $order = $orders->place($slip);

        $this->redirect(route('orders.confirmation', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]));
    }

    public function render(CartService $cart, CheckoutService $checkout, PromptPayQrService $promptPayQr)
    {
        $draft = $checkout->draft();
        $amountDueNow = (float) ($draft['amount_due_now'] ?? $draft['total'] ?? 0);

        return $this->view([
            'draft' => $draft,
            'cartCount' => $cart->count(),
            'promptpayId' => config('booking.promptpay_id'),
            'promptpayName' => config('booking.promptpay_name'),
            'promptpayQrDataUri' => $draft !== null ? $promptPayQr->dataUri($amountDueNow) : null,
        ]);
    }
};
?>

<div class="min-h-dvh bg-bg pb-40 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg px-4 py-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('checkout') }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-surface" aria-label="กลับข้อมูลการจอง">
                <x-icon name="chevron-left" size="lg" />
            </a>
            <h1 class="text-xl font-semibold">ชำระเงิน</h1>
        </div>

        <ol class="mt-4 grid grid-cols-4 gap-1 text-center text-xs text-muted" aria-label="ขั้นตอนการจอง">
            <li class="rounded-brand bg-surface px-1 py-2">ตะกร้า</li>
            <li class="rounded-brand bg-surface px-1 py-2">ข้อมูล</li>
            <li class="rounded-brand bg-accent px-1 py-2 font-medium text-accent-fg" aria-current="step">ชำระเงิน</li>
            <li class="rounded-brand bg-surface px-1 py-2">เสร็จสิ้น</li>
        </ol>

        @if ($draft)
            <section class="mt-6 rounded-brand border border-border bg-surface p-5">
                <div class="flex justify-between gap-3 text-sm">
                    <span>ยอดสินค้า</span>
                    <span>฿{{ number_format((float) $draft['subtotal'], 2) }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm">
                    <span>ค่าจัดส่ง</span>
                    <span>฿{{ number_format((float) $draft['shipping'], 2) }}</span>
                </div>
                <div class="mt-3 flex justify-between gap-3 font-medium">
                    <span>ยอดที่ต้องชำระตอนนี้</span>
                    <span class="text-accent">฿{{ number_format((float) ($draft['amount_due_now'] ?? $draft['total']), 2) }}</span>
                </div>
                @if (($draft['payment_mode'] ?? 'full') === \App\Enums\PaymentMode::Deposit->value)
                    <div class="mt-2 flex justify-between gap-3 text-sm text-muted">
                        <span>ยอดรวม</span>
                        <span>฿{{ number_format((float) $draft['total'], 2) }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-3 text-sm text-muted">
                        <span>คงเหลือตอนรับ</span>
                        <span>฿{{ number_format((float) ($draft['amount_remaining'] ?? 0), 2) }}</span>
                    </div>
                @endif
                <p class="mt-3 text-sm text-muted">ชำระผ่าน PromptPay แล้วแนบสลิปเพื่อยืนยันการจองในขั้นตอนเดียวกัน</p>
            </section>

            <section class="mt-4 rounded-brand border border-border bg-surface p-5 text-center">
                <img
                    src="{{ asset('images/Thai_QR_Logo.svg') }}"
                    alt="Thai QR Payment"
                    class="mx-auto h-8 w-auto"
                >
                @if ($promptpayQrDataUri)
                    <img
                        src="{{ $promptpayQrDataUri }}"
                        alt="PromptPay QR สำหรับชำระ ฿{{ number_format((float) ($draft['amount_due_now'] ?? $draft['total']), 2) }}"
                        width="250"
                        height="250"
                        class="mx-auto mt-4 aspect-square w-48 rounded-brand border border-border bg-white"
                    >
                @endif
                <p class="mt-3 text-sm">{{ $promptpayName }}</p>
                <p class="font-medium tracking-wide">{{ $promptpayId }}</p>
                <p class="mt-2 text-sm text-accent">฿{{ number_format((float) ($draft['amount_due_now'] ?? $draft['total']), 2) }}</p>
            </section>

            <p class="mt-4 text-sm text-muted" id="slip-hint">ต้องแนบสลิปก่อนกดยืนยันการจอง</p>

            <label class="mt-2 flex min-h-24 cursor-pointer flex-col justify-center rounded-brand border border-dashed border-border bg-surface px-4 py-4">
                <span class="font-medium">แนบสลิปการโอน</span>
                <span class="text-sm text-muted">รูปภาพหรือ PDF ไม่เกิน 5MB</span>
                <input type="file" wire:model="slip" accept="image/*,.pdf" class="mt-3 text-sm" aria-describedby="slip-hint">
                @if ($slip)
                    <span class="mt-2 text-sm text-success">แนบแล้ว: {{ $slip->getClientOriginalName() }}</span>
                @endif
            </label>
            @error('slip') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        @endif
    </main>

    <div class="fixed inset-x-0 bottom-14 z-10 border-t border-border bg-surface">
        <div class="mx-auto max-w-lg px-4 py-3">
            <button
                type="button"
                wire:click="confirm"
                wire:loading.attr="disabled"
                wire:target="confirm"
                @disabled(! $slip)
                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-brand bg-accent px-4 font-medium text-accent-fg hover:bg-accent-press disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="confirm">ยืนยันการจอง</span>
                <span wire:loading wire:target="confirm" class="inline-flex items-center gap-2">
                    <x-icon name="arrow-path" size="sm" class="animate-spin" />
                </span>
            </button>
        </div>
    </div>

    <x-storefront.tabbar />
</div>
