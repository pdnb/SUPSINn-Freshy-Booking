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
        try {
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
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors();

            if ($errors->hasAny(['cart', 'product', 'qty', 'options', 'components'])) {
                $this->dispatch('storefront-toast', message: $errors->first());

                return;
            }

            throw $exception;
        }

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

    <main id="content" class="mx-auto max-w-lg p-4">
        <div class="flex items-center gap-2">
            <x-storefront.button variant="ghost" :href="route('cart')" aria-label="กลับตะกร้า">
                <x-icon name="chevron-left" size="lg" />
            </x-storefront.button>
            <h1 class="text-xl font-semibold">ข้อมูลการจอง</h1>
        </div>

        <x-storefront.step-bar :steps="['ตะกร้า', 'ข้อมูล', 'ชำระเงิน', 'เสร็จสิ้น']" current="ข้อมูล" />

        <form class="mt-6 space-y-6">
            <x-storefront.card as="section" class="space-y-4">
                <h2 class="text-base font-semibold">ข้อมูลนักศึกษา</h2>

                <x-storefront.field label="รหัสนักศึกษา" name="student_id">
                    <x-storefront.input id="student_id" type="text" inputmode="numeric" autocomplete="off" wire:model="student_id" />
                </x-storefront.field>

                <x-storefront.field label="ชื่อ-นามสกุล" name="full_name">
                    <x-storefront.input id="full_name" type="text" autocomplete="name" wire:model="full_name" />
                </x-storefront.field>

                <x-storefront.field label="คณะ" name="faculty">
                    <x-storefront.select id="faculty" wire:model="faculty">
                        <option value="">เลือกคณะ</option>
                        @foreach ($faculties as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </x-storefront.select>
                </x-storefront.field>

                <x-storefront.field label="สาขาวิชา" name="major">
                    <x-storefront.input id="major" type="text" autocomplete="organization-title" wire:model="major" placeholder="เช่น วิทยาการคอมพิวเตอร์" />
                </x-storefront.field>

                <x-storefront.field label="เบอร์โทรศัพท์" name="phone">
                    <x-storefront.input id="phone" type="tel" inputmode="tel" autocomplete="tel" wire:model="phone" />
                </x-storefront.field>
            </x-storefront.card>

            <x-storefront.card as="section" class="space-y-4">
                <h2 class="text-base font-semibold">วิธีรับสินค้า</h2>

                <div class="space-y-2" role="radiogroup" aria-label="วิธีรับสินค้า">
                    @foreach ($methods as $method)
                        <x-storefront.radio-card
                            wire:key="fulfill-{{ $method->value }}"
                            wire:model.live="fulfillment"
                            value="{{ $method->value }}"
                            :title="$method->label()"
                            :caption="$method->caption()"
                        />
                    @endforeach
                </div>

                @if ($isPost)
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold">ที่อยู่จัดส่ง</h3>

                        <x-storefront.field label="บ้านเลขที่ ถนน หมู่บ้าน/อาคาร" name="address_line">
                            <x-storefront.input id="address_line" type="text" autocomplete="address-line1" wire:model="address_line" placeholder="เช่น 99 หมู่ 1 ถนนมหาวิทยาลัย" />
                        </x-storefront.field>

                        <div class="grid grid-cols-2 gap-3">
                            <x-storefront.field label="ตำบล" name="subdistrict">
                                <x-storefront.input id="subdistrict" type="text" autocomplete="address-level3" wire:model="subdistrict" placeholder="ตำบล/แขวง" />
                            </x-storefront.field>
                            <x-storefront.field label="อำเภอ" name="district">
                                <x-storefront.input id="district" type="text" autocomplete="address-level2" wire:model="district" placeholder="อำเภอ/เขต" />
                            </x-storefront.field>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <x-storefront.field label="จังหวัด" name="province">
                                <x-storefront.input id="province" type="text" autocomplete="address-level1" wire:model="province" placeholder="จังหวัด" />
                            </x-storefront.field>
                            <x-storefront.field label="รหัสไปรษณีย์" name="postcode">
                                <x-storefront.input id="postcode" type="text" inputmode="numeric" maxlength="5" autocomplete="postal-code" wire:model="postcode" placeholder="xxxxx" />
                            </x-storefront.field>
                        </div>
                    </div>
                @endif
            </x-storefront.card>

            @if ($depositEligible)
                <x-storefront.card as="section" class="space-y-4">
                    <h2 class="text-base font-semibold">การชำระเงิน</h2>
                    <p class="text-sm text-muted">เลือกจ่ายเต็มตอนนี้ หรือมัดจำแล้วชำระส่วนที่เหลือตอนรับสินค้า</p>
                    <div class="space-y-2" role="radiogroup" aria-label="การชำระเงิน">
                        <x-storefront.radio-card
                            wire:model.live="payment_mode"
                            value="{{ \App\Enums\PaymentMode::Full->value }}"
                            title="จ่ายเต็ม"
                            :caption="'฿'.number_format((float) $quote['total'], 2)"
                        />
                        <x-storefront.radio-card
                            wire:model.live="payment_mode"
                            value="{{ \App\Enums\PaymentMode::Deposit->value }}"
                            :title="'มัดจำ ฿'.number_format((float) $depositAmount, 2)"
                            :caption="'เหลือ ฿'.number_format((float) $quote['total'] - (float) $depositAmount, 2).' ตอนรับสินค้า'"
                        />
                    </div>
                    @error('payment_mode') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </x-storefront.card>
            @endif

            <x-storefront.card as="section">
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
                    <x-storefront.price :amount="$quote['amount_due_now'] ?? $quote['total']" />
                </div>
            </x-storefront.card>
        </form>
    </main>

    <x-storefront.bottom-bar>
        <x-storefront.button wire:click="save" block>ไปชำระเงิน</x-storefront.button>
    </x-storefront.bottom-bar>

    <x-storefront.tabbar />
</div>
