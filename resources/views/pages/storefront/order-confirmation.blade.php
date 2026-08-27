<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Services\Payment\PromptPayQrService;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('คำสั่งซื้อ')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $orderId;

    public mixed $slip = null;

    public function mount(string $order, string $token, OrderService $orders): void
    {
        $tracked = $orders->findForGuestTracking($order, $token);

        if ($tracked === null) {
            abort(404);
        }

        $orders->rememberGuestTracking($tracked);

        $this->orderId = $tracked->id;
    }

    public function resubmit(OrderService $orders): void
    {
        $this->validate([
            'slip' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'slip.required' => 'กรุณาแนบสลิป',
        ]);

        /** @var TemporaryUploadedFile $slip */
        $slip = $this->slip;

        $order = Order::query()->findOrFail($this->orderId);

        $orders->replaceSlip($order, $slip);

        $this->reset('slip');
    }

    public function render(CartService $cart, PromptPayQrService $promptPayQr)
    {
        $order = Order::query()->with(['items', 'slip'])->findOrFail($this->orderId);
        $amountDueNow = (float) $order->amount_due_now;

        return $this->view([
            'order' => $order,
            'cartCount' => $cart->count(),
            'steps' => $this->trackingSteps($order),
            'receiptNote' => $this->receiptNote($order),
            'needsReslip' => $order->status === OrderStatus::NeedReslip,
            'promptpayId' => config('booking.promptpay_id'),
            'promptpayName' => config('booking.promptpay_name'),
            'promptpayQrDataUri' => $order->status === OrderStatus::NeedReslip
                ? $promptPayQr->dataUri($amountDueNow)
                : null,
        ]);
    }

    private function receiptNote(Order $order): ?string
    {
        if ($order->receipt_issued_at !== null) {
            return 'ออกใบเสร็จแล้ว '.$order->receipt_issued_at->toThaiDatetime();
        }

        if ($order->fulfillment->chargesShipping()) {
            return null;
        }

        return 'ใบเสร็จจะได้รับตอนรับสินค้า';
    }

    /**
     * @return list<array{label: string, state: 'done'|'current'|'upcoming'}>
     */
    private function trackingSteps(Order $order): array
    {
        $labels = $order->fulfillment->chargesShipping()
            ? ['จองแล้ว', 'ตรวจสลิป', 'จัดส่งแล้ว']
            : ['จองแล้ว', 'ตรวจสลิป', 'พร้อมรับ', 'รับแล้ว'];

        if ($order->status === OrderStatus::Completed
            || ($order->fulfillment->chargesShipping() && $order->status === OrderStatus::Shipped)) {
            return collect($labels)
                ->map(fn (string $label): array => ['label' => $label, 'state' => 'done'])
                ->all();
        }

        $currentIndex = match ($order->status) {
            OrderStatus::PendingReview, OrderStatus::NeedReslip, OrderStatus::Cancelled => 1,
            OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::ReadyForPickup => 2,
        };

        return collect($labels)->map(function (string $label, int $index) use ($currentIndex): array {
            return [
                'label' => $label,
                'state' => match (true) {
                    $index < $currentIndex => 'done',
                    $index === $currentIndex => 'current',
                    default => 'upcoming',
                },
            ];
        })->all();
    }
};
?>

<div class="@class(['min-h-dvh bg-bg text-fg', 'pb-52' => $needsReslip, 'pb-20' => ! $needsReslip])">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg p-4">
        <div class="flex items-center gap-2">
            <x-storefront.button variant="ghost" :href="route('orders.index')" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </x-storefront.button>
            <h1 class="text-xl font-semibold">คำสั่งซื้อ</h1>
        </div>

        <x-storefront.step-bar variant="order" :steps="$steps" label="สถานะคำสั่งซื้อ" />

        <x-storefront.card as="section" class="mt-4">
            <p class="flex items-center justify-between gap-3 text-xs font-medium text-brand">
                <span>รหัสออเดอร์</span>
                <button
                    type="button"
                    class="-mr-1 inline-flex min-h-11 items-center gap-1 rounded-brand px-1 hover:bg-bg"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(@js($order->number)).then(() => { copied = true; setTimeout(() => copied = false, 1600) })"
                    aria-label="คัดลอกรหัสออเดอร์ {{ $order->number }}"
                    x-bind:aria-label="copied ? 'คัดลอกรหัสออเดอร์แล้ว' : @js('คัดลอกรหัสออเดอร์ '.$order->number)"
                >
                    <span x-text="copied ? 'คัดลอกแล้ว' : @js($order->number)">{{ $order->number }}</span>
                    <x-icon name="clipboard-document" size="sm" />
                </button>
            </p>
            <h2 class="mt-1 text-xl font-semibold">{{ $order->status->label() }}</h2>
            @if ($receiptNote)
                <p class="mt-1 text-sm text-muted">{{ $receiptNote }}</p>
            @endif
            @if (filled($order->parcel_number))
                <p class="mt-3 flex items-center justify-between gap-3 text-xs font-medium text-brand">
                    <span>เลขพัสดุ</span>
                    <button
                        type="button"
                        class="-mr-1 inline-flex min-h-11 items-center gap-1 rounded-brand px-1 hover:bg-bg"
                        x-data="{ copied: false }"
                        x-on:click="navigator.clipboard.writeText(@js($order->parcel_number)).then(() => { copied = true; setTimeout(() => copied = false, 1600) })"
                        aria-label="คัดลอกเลขพัสดุ {{ $order->parcel_number }}"
                        x-bind:aria-label="copied ? 'คัดลอกเลขพัสดุแล้ว' : @js('คัดลอกเลขพัสดุ '.$order->parcel_number)"
                    >
                        <span x-text="copied ? 'คัดลอกแล้ว' : @js($order->parcel_number)">{{ $order->parcel_number }}</span>
                        <x-icon name="clipboard-document" size="sm" />
                    </button>
                </p>
            @endif
            @if ($order->status === \App\Enums\OrderStatus::PendingReview)
                <p class="mt-3 text-sm text-muted">สลิปผ่านการตรวจเบื้องต้นแล้ว รอเจ้าหน้าที่ยืนยัน — ยังไม่ถือว่าชำระแล้ว</p>
            @endif
            @if ($needsReslip)
                <p class="mt-3 text-sm text-muted">กรุณาชำระเงินอีกครั้งและแนบสลิปใหม่เพื่อเข้าคิวตรวจ</p>
            @endif
        </x-storefront.card>

        @if ($needsReslip)
            <x-storefront.card as="section" padding="5" class="mt-4">
                <div class="flex justify-between gap-3 text-sm">
                    <span>ยอดสินค้า</span>
                    <span>฿{{ number_format((float) $order->subtotal, 2) }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm">
                    <span>ค่าจัดส่ง</span>
                    <span>฿{{ number_format((float) $order->shipping_amount, 2) }}</span>
                </div>
                <div class="mt-3 flex justify-between gap-3 font-medium">
                    <span>ยอดที่ต้องชำระตอนนี้</span>
                    <x-storefront.price :amount="$order->amount_due_now" />
                </div>
                @if ($order->payment_mode === \App\Enums\PaymentMode::Deposit)
                    <div class="mt-2 flex justify-between gap-3 text-sm text-muted">
                        <span>ยอดรวม</span>
                        <span>฿{{ number_format((float) $order->total, 2) }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-3 text-sm text-muted">
                        <span>คงเหลือตอนรับ</span>
                        <span>฿{{ number_format((float) $order->amount_remaining, 2) }}</span>
                    </div>
                @endif
                <p class="mt-3 text-sm text-muted">ชำระผ่าน PromptPay แล้วแนบสลิปเพื่อส่งเข้าคิวตรวจอีกครั้ง</p>
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
                        alt="PromptPay QR สำหรับชำระ ฿{{ number_format((float) $order->amount_due_now, 2) }}"
                        width="250"
                        height="250"
                        class="mx-auto mt-4 aspect-square w-48 rounded-brand border border-border bg-white"
                    >
                @endif
                <p class="mt-3 text-sm">{{ $promptpayName }}</p>
                <p class="font-medium tracking-wide">{{ $promptpayId }}</p>
                <x-storefront.price :amount="$order->amount_due_now" size="sm" class="mt-2" />
            </x-storefront.card>

            <x-storefront.slip-dropzone
                class="mt-4"
                wire:model="slip"
                :filename="$slip?->getClientOriginalName()"
                :preview-url="$slip?->isPreviewable() ? $slip->temporaryUrl() : null"
            />
            @error('slip') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        @endif

        <x-storefront.card as="section" padding="0" class="mt-4 px-4">
            <dl class="text-sm">
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">ผู้จอง</dt>
                    <dd class="text-right">{{ $order->full_name }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">รหัสนักศึกษา</dt>
                    <dd class="text-right">{{ $order->student_id }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">คณะ</dt>
                    <dd class="text-right">{{ $order->faculty }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">สาขาวิชา</dt>
                    <dd class="text-right">{{ $order->major }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">เบอร์โทร</dt>
                    <dd class="text-right">{{ $order->phone }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-border py-2">
                    <dt class="text-muted">วิธีรับ</dt>
                    <dd class="text-right">{{ $order->fulfillment->label() }}</dd>
                </div>
                @if ($order->address)
                    <div class="flex justify-between gap-3 border-b border-border py-2">
                        <dt class="text-muted">ที่อยู่</dt>
                        <dd class="text-right">{{ $order->address }}</dd>
                    </div>
                @endif
                @if (! $needsReslip)
                    <div class="flex justify-between gap-3 py-2">
                        <dt class="text-muted">สลิป</dt>
                        <dd class="min-w-0 text-right">
                            @if ($order->slip)
                                <div x-data="{ open: false }">
                                    <button
                                        type="button"
                                        class="-my-1 inline-flex min-h-11 max-w-full items-center justify-end break-all text-right text-brand hover:underline"
                                        x-on:click="open = true; $nextTick(() => $refs.preview.showModal())"
                                        aria-haspopup="dialog"
                                        aria-controls="slip-preview"
                                        aria-label="ดูสลิป {{ $order->slip->original_name }}"
                                    >
                                        {{ $order->slip->original_name }}
                                    </button>
                                    <dialog
                                        id="slip-preview"
                                        x-ref="preview"
                                        class="m-auto w-[min(100%,32rem)] max-w-[calc(100%-2rem)] rounded-brand border border-border bg-surface p-4 text-fg backdrop:bg-fg/50"
                                        x-on:close="open = false"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <h2 class="text-sm font-medium">สลิป</h2>
                                            <x-storefront.button
                                                variant="ghost"
                                                x-on:click="$refs.preview.close()"
                                                aria-label="ปิดตัวอย่างสลิป"
                                            >
                                                <x-icon name="x-mark" size="md" />
                                            </x-storefront.button>
                                        </div>
                                        @if (str_ends_with(mb_strtolower($order->slip->original_name), '.pdf'))
                                            <iframe
                                                x-show="open"
                                                src="{{ route('orders.slip', $order) }}"
                                                title="สลิป {{ $order->slip->original_name }}"
                                                class="mt-3 h-[70vh] w-full rounded-brand border border-border bg-bg"
                                            ></iframe>
                                        @else
                                            <img
                                                x-show="open"
                                                src="{{ route('orders.slip', $order) }}"
                                                alt="สลิป {{ $order->slip->original_name }}"
                                                class="mt-3 max-h-[70vh] w-full object-contain"
                                            >
                                        @endif
                                    </dialog>
                                </div>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </x-storefront.card>

        <section class="mt-5">
            <h2 class="font-semibold">รายการที่จอง</h2>
            <ul class="mt-3 space-y-3">
                @forelse ($order->items as $item)
                    <x-storefront.card as="li" class="text-sm" wire:key="guest-item-{{ $item->id }}">
                        <div class="flex justify-between gap-3 font-medium">
                            <span>{{ $item->name }}</span>
                            <span>× {{ $item->qty }}</span>
                        </div>
                        @if ($item->choices)
                            <ul class="mt-2 space-y-0.5 text-muted">
                                @foreach ($item->choices as $index => $choice)
                                    <li wire:key="guest-choice-{{ $item->id }}-{{ $index }}">
                                        @if (is_array($choice) && isset($choice['label'], $choice['value']))
                                            {{ $choice['label'] }} · {{ $choice['value'] }}
                                        @else
                                            {{ is_scalar($choice) ? $choice : collect($choice)->flatten()->filter()->implode(' · ') }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="mt-1 text-right text-muted">{{ number_format((float) $item->price, 2) }} บาท</p>
                    </x-storefront.card>
                @empty
                    <li class="text-sm text-muted">ไม่มีรายการ</li>
                @endforelse
            </ul>
            <x-storefront.card as="dl" class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">ยอดสินค้า</dt>
                    <dd>{{ number_format((float) $order->subtotal, 2) }} บาท</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">ค่าส่ง</dt>
                    <dd>{{ number_format((float) $order->shipping_amount, 2) }} บาท</dd>
                </div>
                <div class="flex justify-between gap-3 border-t border-border pt-2 font-medium">
                    <dt>รวม</dt>
                    <dd>{{ number_format((float) $order->total, 2) }} บาท</dd>
                </div>
                @if ($order->payment_mode === \App\Enums\PaymentMode::Deposit)
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">จ่ายตอนสั่ง (มัดจำ)</dt>
                        <dd>{{ number_format((float) $order->amount_due_now, 2) }} บาท</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">คงเหลือตอนรับ</dt>
                        <dd>{{ number_format((float) $order->amount_remaining, 2) }} บาท</dd>
                    </div>
                @endif
            </x-storefront.card>
        </section>

        @if (! $order->fulfillment->chargesShipping())
            <section class="mt-6">
                <h2 class="font-semibold">จุดรับสินค้า</h2>
                <x-storefront.card as="article" class="mt-3">
                    <h3 class="font-medium">{{ $order->fulfillment->label() }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $order->fulfillment->caption() }}</p>
                </x-storefront.card>
            </section>
        @endif
    </main>

    @if ($needsReslip)
        <x-storefront.bottom-bar>
            <x-storefront.button wire:click="resubmit" :disabled="! $slip" block>ส่งสลิปใหม่</x-storefront.button>
        </x-storefront.bottom-bar>
    @endif

    <x-storefront.tabbar />
</div>
