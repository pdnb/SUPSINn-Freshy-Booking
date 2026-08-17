<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PaymentMode;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('ข้อมูลการจอง')] class extends Component
{
    public string $student_id = '';

    public string $full_name = '';

    public string $faculty = '';

    public string $major = '';

    public string $phone = '';

    public string $fulfillment = 'bookstore';

    public string $payment_mode = 'full';

    public string $address_line = '';

    public string $subdistrict = '';

    public string $district = '';

    public string $province = '';

    public string $postcode = '';

    public string $shipping_rate_id = '';

    public function mount(CartService $cart, CheckoutService $checkout): void
    {
        if ($cart->items()->isEmpty()) {
            $this->redirect(route('cart'), navigate: true);

            return;
        }

        $draft = $checkout->draft();

        if (! $draft) {
            return;
        }

        $this->student_id = (string) ($draft['student_id'] ?? '');
        $this->full_name = (string) ($draft['full_name'] ?? '');
        $this->faculty = (string) ($draft['faculty'] ?? '');
        $this->major = (string) ($draft['major'] ?? '');
        $this->phone = (string) ($draft['phone'] ?? '');
        $this->fulfillment = (string) ($draft['fulfillment'] ?? FulfillmentMethod::Bookstore->value);
        $this->payment_mode = (string) ($draft['payment_mode'] ?? PaymentMode::Full->value);
        $this->address_line = (string) ($draft['address_line'] ?? '');
        $this->subdistrict = (string) ($draft['subdistrict'] ?? '');
        $this->district = (string) ($draft['district'] ?? '');
        $this->province = (string) ($draft['province'] ?? '');
        $this->postcode = (string) ($draft['postcode'] ?? '');
        $this->shipping_rate_id = isset($draft['shipping_rate_id']) ? (string) $draft['shipping_rate_id'] : '';
    }

    public function updatedFulfillment(): void
    {
        if ($this->fulfillment === FulfillmentMethod::Post->value) {
            $this->payment_mode = PaymentMode::Full->value;
        }
    }

    public function save(CheckoutService $checkout): void
    {
        $checkout->save([
            'student_id' => $this->student_id,
            'full_name' => $this->full_name,
            'faculty' => $this->faculty,
            'major' => $this->major,
            'phone' => $this->phone,
            'fulfillment' => $this->fulfillment,
            'payment_mode' => $this->payment_mode,
            'address_line' => $this->address_line !== '' ? $this->address_line : null,
            'subdistrict' => $this->subdistrict !== '' ? $this->subdistrict : null,
            'district' => $this->district !== '' ? $this->district : null,
            'province' => $this->province !== '' ? $this->province : null,
            'postcode' => $this->postcode !== '' ? $this->postcode : null,
            'shipping_rate_id' => $this->shipping_rate_id !== '' ? (int) $this->shipping_rate_id : null,
        ]);

        $this->redirect(route('pay'), navigate: true);
    }

    public function render(CheckoutService $checkout, CartService $cart, ShippingRateService $shipping)
    {
        $quote = [
            'subtotal' => $cart->subtotal(),
            'shipping' => '0.00',
            'total' => $cart->subtotal(),
            'payment_mode' => PaymentMode::Full->value,
            'amount_due_now' => $cart->subtotal(),
            'amount_remaining' => '0.00',
        ];

        try {
            if ($cart->items()->isNotEmpty()) {
                $quote = $checkout->quote(
                    $this->fulfillment,
                    $this->shipping_rate_id !== '' ? (int) $this->shipping_rate_id : null,
                    $this->payment_mode,
                );
            }
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        }

        $depositEligible = $checkout->depositEligible($this->fulfillment, (string) $quote['total']);

        if (! $depositEligible && $this->payment_mode === PaymentMode::Deposit->value) {
            $this->payment_mode = PaymentMode::Full->value;
            $quote['payment_mode'] = PaymentMode::Full->value;
            $quote['amount_due_now'] = $quote['total'];
            $quote['amount_remaining'] = '0.00';
        }

        return $this->view([
            'faculties' => $checkout->faculties(),
            'methods' => FulfillmentMethod::cases(),
            'rates' => $shipping->active(),
            'quote' => $quote,
            'isPost' => $this->fulfillment === FulfillmentMethod::Post->value,
            'depositEligible' => $depositEligible,
            'depositAmount' => $checkout->depositAmount(),
            'cartCount' => $cart->count(),
        ]);
    }
};
?>

<div class="min-h-dvh bg-bg pb-40 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg px-4 py-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('cart') }}" wire:navigate class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-brand hover:bg-surface" aria-label="กลับตะกร้า">
                <x-icon name="chevron-left" size="lg" />
            </a>
            <h1 class="text-xl font-semibold">ข้อมูลการจอง</h1>
        </div>

        <ol class="mt-4 grid grid-cols-4 gap-1 text-center text-xs text-muted" aria-label="ขั้นตอนการจอง">
            <li class="rounded-brand bg-surface px-1 py-2">ตะกร้า</li>
            <li class="rounded-brand bg-accent px-1 py-2 font-medium text-accent-fg" aria-current="step">ข้อมูล</li>
            <li class="rounded-brand bg-surface px-1 py-2">ชำระเงิน</li>
            <li class="rounded-brand bg-surface px-1 py-2">เสร็จสิ้น</li>
        </ol>

        <form class="mt-6 space-y-6">
            <section class="space-y-4 rounded-brand border border-border bg-surface p-4">
                <h2 class="text-base font-semibold">ข้อมูลนักศึกษา</h2>

                <div>
                    <label for="student_id" class="block text-sm font-medium">รหัสนักศึกษา</label>
                    <input id="student_id" type="text" inputmode="numeric" autocomplete="off" wire:model="student_id" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent">
                    @error('student_id') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="full_name" class="block text-sm font-medium">ชื่อ-นามสกุล</label>
                    <input id="full_name" type="text" autocomplete="name" wire:model="full_name" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent">
                    @error('full_name') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="faculty" class="block text-sm font-medium">คณะ</label>
                    <select id="faculty" wire:model="faculty" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent">
                        <option value="">เลือกคณะ</option>
                        @foreach ($faculties as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('faculty') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="major" class="block text-sm font-medium">สาขาวิชา</label>
                    <input id="major" type="text" autocomplete="organization-title" wire:model="major" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent" placeholder="เช่น วิทยาการคอมพิวเตอร์">
                    @error('major') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium">เบอร์โทรศัพท์</label>
                    <input id="phone" type="tel" inputmode="tel" autocomplete="tel" wire:model="phone" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent">
                    @error('phone') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="space-y-4 rounded-brand border border-border bg-surface p-4">
                <h2 class="text-base font-semibold">วิธีรับสินค้า</h2>

                <div class="space-y-2" role="radiogroup" aria-label="วิธีรับสินค้า">
                    @foreach ($methods as $method)
                        <label class="flex min-h-11 items-start gap-3 rounded-brand border border-border p-3" wire:key="fulfill-{{ $method->value }}">
                            <input type="radio" wire:model.live="fulfillment" value="{{ $method->value }}" class="mt-1">
                            <span>
                                <span class="block font-medium">{{ $method->label() }}</span>
                                <span class="block text-sm text-muted">{{ $method->caption() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @if ($isPost)
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold">ที่อยู่จัดส่ง</h3>

                        <div>
                            <label for="address_line" class="block text-sm font-medium">บ้านเลขที่ ถนน หมู่บ้าน/อาคาร</label>
                            <input id="address_line" type="text" autocomplete="address-line1" wire:model="address_line" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent" placeholder="เช่น 99 หมู่ 1 ถนนมหาวิทยาลัย">
                            @error('address_line') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="subdistrict" class="block text-sm font-medium">ตำบล</label>
                                <input id="subdistrict" type="text" autocomplete="address-level3" wire:model="subdistrict" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent" placeholder="ตำบล/แขวง">
                                @error('subdistrict') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="district" class="block text-sm font-medium">อำเภอ</label>
                                <input id="district" type="text" autocomplete="address-level2" wire:model="district" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent" placeholder="อำเภอ/เขต">
                                @error('district') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="province" class="block text-sm font-medium">จังหวัด</label>
                                <input id="province" type="text" autocomplete="address-level1" wire:model="province" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent" placeholder="จังหวัด">
                                @error('province') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="postcode" class="block text-sm font-medium">รหัสไปรษณีย์</label>
                                <input id="postcode" type="text" inputmode="numeric" maxlength="5" autocomplete="postal-code" wire:model="postcode" class="mt-1 min-h-11 w-full rounded-brand border border-border px-3 focus:ring-2 focus:ring-accent" placeholder="xxxxx">
                                @error('postcode') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            @if ($depositEligible)
                <section class="space-y-4 rounded-brand border border-border bg-surface p-4">
                    <h2 class="text-base font-semibold">การชำระเงิน</h2>
                    <p class="text-sm text-muted">เลือกจ่ายเต็มตอนนี้ หรือมัดจำแล้วชำระส่วนที่เหลือตอนรับสินค้า</p>
                    <div class="space-y-2" role="radiogroup" aria-label="การชำระเงิน">
                        <label class="flex min-h-11 items-start gap-3 rounded-brand border border-border p-3">
                            <input type="radio" wire:model.live="payment_mode" value="{{ \App\Enums\PaymentMode::Full->value }}" class="mt-1">
                            <span>
                                <span class="block font-medium">จ่ายเต็ม</span>
                                <span class="block text-sm text-muted">฿{{ number_format((float) $quote['total'], 2) }}</span>
                            </span>
                        </label>
                        <label class="flex min-h-11 items-start gap-3 rounded-brand border border-border p-3">
                            <input type="radio" wire:model.live="payment_mode" value="{{ \App\Enums\PaymentMode::Deposit->value }}" class="mt-1">
                            <span>
                                <span class="block font-medium">มัดจำ ฿{{ number_format((float) $depositAmount, 2) }}</span>
                                <span class="block text-sm text-muted">เหลือ ฿{{ number_format((float) $quote['total'] - (float) $depositAmount, 2) }} ตอนรับสินค้า</span>
                            </span>
                        </label>
                    </div>
                    @error('payment_mode') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </section>
            @endif

            <section class="rounded-brand border border-border bg-surface p-4">
                <div class="flex justify-between gap-3 text-sm">
                    <span>ราคาสินค้า</span>
                    <span>฿{{ number_format((float) $quote['subtotal'], 2) }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm">
                    <span>ค่าจัดส่ง</span>
                    <span>฿{{ number_format((float) $quote['shipping'], 2) }}</span>
                </div>
                @if (($quote['payment_mode'] ?? 'full') === \App\Enums\PaymentMode::Deposit->value)
                    <div class="mt-2 flex justify-between gap-3 text-sm">
                        <span>ยอดรวม</span>
                        <span>฿{{ number_format((float) $quote['total'], 2) }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-3 text-sm text-muted">
                        <span>คงเหลือตอนรับ</span>
                        <span>฿{{ number_format((float) $quote['amount_remaining'], 2) }}</span>
                    </div>
                @endif
                <div class="mt-3 flex justify-between gap-3 font-medium">
                    <span>ยอดที่ต้องชำระตอนนี้</span>
                    <span class="text-accent">฿{{ number_format((float) ($quote['amount_due_now'] ?? $quote['total']), 2) }}</span>
                </div>
            </section>

            @error('cart') <p class="text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </form>
    </main>

    <div class="fixed inset-x-0 bottom-14 z-10 border-t border-border bg-surface">
        <div class="mx-auto max-w-lg px-4 py-3">
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-brand bg-accent px-4 font-medium text-accent-fg hover:bg-accent-press disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">ไปชำระเงิน</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <x-icon name="arrow-path" size="sm" class="animate-spin" />
                </span>
            </button>
        </div>
    </div>

    <x-storefront.tabbar />
</div>
