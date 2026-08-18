<?php

use App\Services\Cart\CartService;
use App\Services\Line\LineIdentityService;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('คำสั่งซื้อ')] class extends Component
{
    public string $student_id = '';

    public string $phone = '';

    public bool $lookedUp = false;

    public function mount(OrderService $orders, LineIdentityService $line): void
    {
        if ($line->userId() !== null) {
            return;
        }

        $order = $orders->trackedGuestOrder();

        if ($order === null) {
            return;
        }

        $this->redirect(route('orders.confirmation', [
            'order' => $order,
            'token' => $order->tracking_token,
        ]), navigate: true);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['student_id', 'phone'], true)) {
            $this->lookedUp = false;
        }
    }

    public function search(OrderService $orders, LineIdentityService $line): void
    {
        if ($line->userId() !== null) {
            return;
        }

        $this->student_id = trim($this->student_id);
        $this->phone = Str::replaceMatches('/\D+/', '', trim($this->phone));

        $this->validate([
            'student_id' => ['required', 'string', 'regex:/^\d{8,13}$/'],
            'phone' => ['required', 'string', 'regex:/^0\d{8,9}$/'],
        ], [
            'student_id.required' => 'กรุณากรอกรหัสนักศึกษา',
            'student_id.regex' => 'รหัสนักศึกษาไม่ถูกต้อง',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex' => 'เบอร์โทรศัพท์ไม่ถูกต้อง',
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('lookup', "ลองใหม่ในอีก {$seconds} วินาที");

            return;
        }

        $matches = $orders->findForGuestLookup($this->student_id, $this->phone);

        if ($matches->isEmpty()) {
            RateLimiter::increment($throttleKey);
            $this->lookedUp = false;
            $this->addError('lookup', 'ไม่พบคำสั่งซื้อที่ตรงกับข้อมูลนี้');

            return;
        }

        RateLimiter::clear($throttleKey);
        $this->lookedUp = true;
    }

    public function render(CartService $cart, LineIdentityService $line, OrderService $orders)
    {
        $hasLineIdentity = $line->userId() !== null;
        $lineOrders = $line->ordersForCurrentUser();
        $guestOrders = $this->lookedUp && ! $hasLineIdentity
            ? $orders->findForGuestLookup($this->student_id, $this->phone)
            : collect();
        $listedOrders = $hasLineIdentity ? $lineOrders : $guestOrders;

        return $this->view([
            'cartCount' => $cart->count(),
            'hasLineIdentity' => $hasLineIdentity,
            'listedOrders' => $listedOrders,
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate('order-lookup|'.request()->ip());
    }
};
?>

<div class="min-h-dvh bg-bg pb-20 text-fg">
    <x-storefront.header :cart-count="$cartCount" />

    <main id="content" class="mx-auto max-w-lg p-4">
        <div class="flex items-center gap-2">
            <x-storefront.button variant="ghost" :href="route('home')" aria-label="กลับหน้าหลัก">
                <x-icon name="chevron-left" size="lg" />
            </x-storefront.button>
            <h1 class="text-xl font-semibold">คำสั่งซื้อ</h1>
        </div>

        @if ($hasLineIdentity && $listedOrders->isEmpty())
            <x-storefront.empty-state
                class="mt-8"
                icon="shopping-bag"
                kicker="ยังไม่มีการจอง"
                title="ยังไม่มีคำสั่งซื้อ"
                description="เมื่อจองผ่าน LINE และแนบสลิปแล้ว สถานะจะแสดงที่นี่"
            >
                <x-storefront.button variant="secondary" :href="route('home')">ไปหน้าหลัก</x-storefront.button>
            </x-storefront.empty-state>
        @elseif (! $hasLineIdentity)
            <x-storefront.card as="section" class="mt-6 space-y-4">
                <h2 class="text-base font-semibold">ค้นหาคำสั่งซื้อ</h2>

                <x-storefront.field label="รหัสนักศึกษา" name="student_id">
                    <x-storefront.input id="student_id" type="text" inputmode="numeric" autocomplete="off" wire:model="student_id" />
                </x-storefront.field>

                <x-storefront.field label="เบอร์โทรศัพท์" name="phone">
                    <x-storefront.input id="phone" type="tel" inputmode="tel" autocomplete="tel" wire:model="phone" />
                </x-storefront.field>

                @error('lookup')
                    <p class="text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror

                <x-storefront.button wire:click="search" block>ค้นหา</x-storefront.button>
            </x-storefront.card>
        @endif

        @if ($listedOrders->isNotEmpty())
            <ul class="mt-6 space-y-3" aria-label="ออเดอร์ของฉัน">
                @foreach ($listedOrders as $order)
                    <li>
                        <x-storefront.card
                            as="a"
                            href="{{ route('orders.confirmation', ['order' => $order, 'token' => $order->tracking_token]) }}"
                            wire:navigate
                            class="block hover:border-accent"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">{{ $order->number }}</p>
                                    <p class="mt-1 text-sm text-muted">{{ $order->created_at?->toThaiDatetime() }}</p>
                                </div>
                                <p class="text-right text-sm font-medium">{{ number_format((float) $order->total, 2) }} บาท</p>
                            </div>
                            <p class="mt-3 text-sm text-brand">{{ $order->status->label() }}</p>
                        </x-storefront.card>
                    </li>
                @endforeach
            </ul>
        @endif
    </main>

    <x-storefront.tabbar />
</div>
