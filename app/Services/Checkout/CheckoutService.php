<?php

namespace App\Services\Checkout;

use App\Enums\FulfillmentMethod;
use App\Services\Cart\CartService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private CartService $cart,
        private ShippingRateService $shipping,
        private Session $session,
    ) {}

    /**
     * @return list<string>
     */
    public function faculties(): array
    {
        return [
            'คณะครุศาสตร์',
            'คณะมนุษยศาสตร์และสังคมศาสตร์',
            'คณะวิทยาศาสตร์และเทคโนโลยี',
            'คณะวิทยาการจัดการ',
            'คณะเทคโนโลยีการเกษตร',
            'คณะเทคโนโลยีสารสนเทศ',
            'คณะพยาบาลศาสตร์',
            'คณะกฎหมาย',
            'อื่น ๆ / ยังไม่ระบุ',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quote(?string $fulfillment = null, ?int $shippingRateId = null): array
    {
        $this->cart->assertAvailable();

        $method = FulfillmentMethod::tryFrom($fulfillment ?? FulfillmentMethod::Bookstore->value)
            ?? FulfillmentMethod::Bookstore;

        $subtotal = $this->cart->subtotal();
        $shipping = '0.00';
        $rateId = null;
        $rateName = null;

        if ($method->chargesShipping()) {
            $rates = $this->shipping->active();
            $rate = $shippingRateId
                ? $rates->firstWhere('id', $shippingRateId)
                : $rates->first();

            if (! $rate) {
                throw ValidationException::withMessages([
                    'shipping_rate_id' => 'ยังไม่มีเรทค่าส่งที่เปิดใช้',
                ]);
            }

            $shipping = $this->shipping->amountForQty($rate, $this->cart->count());
            $rateId = $rate->id;
            $rateName = $rate->name;
        }

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => number_format((float) $subtotal + (float) $shipping, 2, '.', ''),
            'fulfillment' => $method->value,
            'shipping_rate_id' => $rateId,
            'shipping_rate_name' => $rateName,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data): array
    {
        $payload = $this->validated($data);
        $quote = $this->quote(
            $payload['fulfillment'],
            isset($payload['shipping_rate_id']) ? (int) $payload['shipping_rate_id'] : null,
        );

        $draft = [
            ...$payload,
            ...$quote,
        ];

        $this->session->put('checkout.draft', $draft);

        return $draft;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function draft(): ?array
    {
        /** @var array<string, mixed>|null $draft */
        $draft = $this->session->get('checkout.draft');

        return $draft;
    }

    public function forget(): void
    {
        $this->session->forget('checkout.draft');
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    public function composeAddress(array $parts): string
    {
        return trim(implode(' ', array_filter([
            $parts['address_line'] ?? null,
            isset($parts['subdistrict']) ? 'ตำบล/แขวง '.$parts['subdistrict'] : null,
            isset($parts['district']) ? 'อำเภอ/เขต '.$parts['district'] : null,
            isset($parts['province']) ? 'จังหวัด '.$parts['province'] : null,
            $parts['postcode'] ?? null,
        ], fn (?string $value): bool => $value !== null && $value !== '')));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validated(array $data): array
    {
        $isPost = ($data['fulfillment'] ?? null) === FulfillmentMethod::Post->value;

        $validated = Validator::make($data, [
            'student_id' => ['required', 'string', 'regex:/^\d{8,13}$/'],
            'full_name' => ['required', 'string', 'max:255'],
            'faculty' => ['required', 'string', Rule::in($this->faculties())],
            'major' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^0\d{8,9}$/'],
            'fulfillment' => ['required', Rule::enum(FulfillmentMethod::class)],
            'address_line' => [$isPost ? 'required' : 'nullable', 'string', 'max:500'],
            'subdistrict' => [$isPost ? 'required' : 'nullable', 'string', 'max:120'],
            'district' => [$isPost ? 'required' : 'nullable', 'string', 'max:120'],
            'province' => [$isPost ? 'required' : 'nullable', 'string', 'max:120'],
            'postcode' => [$isPost ? 'required' : 'nullable', 'string', 'regex:/^\d{5}$/'],
            'shipping_rate_id' => ['nullable', 'integer'],
        ], [
            'student_id.required' => 'กรุณากรอกรหัสนักศึกษา',
            'student_id.regex' => 'รหัสนักศึกษาไม่ถูกต้อง',
            'full_name.required' => 'กรุณากรอกชื่อ–นามสกุล',
            'faculty.required' => 'กรุณาเลือกคณะ',
            'major.required' => 'กรุณากรอกสาขาวิชา',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex' => 'เบอร์โทรศัพท์ไม่ถูกต้อง',
            'address_line.required' => 'กรุณากรอกบ้านเลขที่ ถนน หมู่บ้าน/อาคาร',
            'subdistrict.required' => 'กรุณากรอกตำบล / แขวง',
            'district.required' => 'กรุณากรอกอำเภอ / เขต',
            'province.required' => 'กรุณากรอกจังหวัด',
            'postcode.required' => 'กรุณากรอกรหัสไปรษณีย์',
            'postcode.regex' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
        ])->validate();

        if ($isPost) {
            $validated['address'] = $this->composeAddress($validated);
        } else {
            $validated['address'] = null;
            $validated['address_line'] = null;
            $validated['subdistrict'] = null;
            $validated['district'] = null;
            $validated['province'] = null;
            $validated['postcode'] = null;
        }

        return $validated;
    }
}
