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
            $this->redirect(route('cart'), navigate: true);

            return;
        }

        if ($checkout->draft() === null) {
            $this->redirect(route('checkout'), navigate: true);
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
        ]), navigate: true);
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
            <x-storefront.button variant="ghost" :href="route('checkout')" aria-label="กลับข้อมูลการจอง">
                <x-icon name="chevron-left" size="lg" />
            </x-storefront.button>
            <h1 class="text-xl font-semibold">ชำระเงิน</h1>
        </div>

        <x-storefront.step-bar :steps="['ตะกร้า', 'ข้อมูล', 'ชำระเงิน', 'เสร็จสิ้น']" current="ชำระเงิน" />

        @if ($draft)
            <x-storefront.card as="section" padding="5" class="mt-6">
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
                    <x-storefront.price :amount="$draft['amount_due_now'] ?? $draft['total']" />
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
            </x-storefront.card>

            <x-storefront.card as="section" padding="5" class="mt-4 text-center">
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
                <x-storefront.price :amount="$draft['amount_due_now'] ?? $draft['total']" size="sm" class="mt-2" />
            </x-storefront.card>

            <p class="mt-4 text-sm text-muted" id="slip-hint">ต้องแนบสลิปก่อนกดยืนยันการจอง</p>

            <x-storefront.slip-dropzone
                wire:model="slip"
                :filename="$slip?->getClientOriginalName()"
            />
            @error('slip') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        @endif
    </main>

    <x-storefront.bottom-bar>
        <x-storefront.button wire:click="confirm" :disabled="! $slip" block>ยืนยันการจอง</x-storefront.button>
    </x-storefront.bottom-bar>

    <x-storefront.tabbar />
</div>
