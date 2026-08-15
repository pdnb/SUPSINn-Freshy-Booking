<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\BookingRound;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Services\Booking\BookingRoundService;
use App\Services\Catalog\CatalogService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(
        CatalogService $catalog,
        ShippingRateService $shipping,
        BookingRoundService $booking,
    ): void {
        $shirt = $this->upsertProduct($catalog, 'freshy-69-shirt', [
            'name' => 'ชุดเฟรชชี่ ปี 69 — เสื้อ',
            'description' => 'ซื้อแยกได้ ระบุไซส์ได้ต่อรายการ',
            'type' => ProductType::Simple->value,
            'price' => '350.00',
            'is_active' => true,
            'option_groups' => [
                [
                    'key' => 'size',
                    'label' => 'ไซส์เสื้อ',
                    'values' => ['S', 'M', 'L', 'XL', '2XL'],
                ],
            ],
        ]);

        $pants = $this->upsertProduct($catalog, 'freshy-69-pants', [
            'name' => 'ชุดเฟรชชี่ ปี 69 — กางเกง',
            'description' => 'ซื้อแยกได้ ระบุไซส์ได้ต่อรายการ',
            'type' => ProductType::Simple->value,
            'price' => '350.00',
            'is_active' => true,
            'option_groups' => [
                [
                    'key' => 'size',
                    'label' => 'ไซส์กางเกง',
                    'values' => ['S', 'M', 'L', 'XL', '2XL'],
                ],
            ],
        ]);

        $combo = $this->upsertProduct($catalog, 'sru-combo-70', [
            'name' => 'SRU คอมโบเซ็ต ปี 70',
            'description' => 'บังคับซื้อทั้งชุด ไม่ขายแยกชิ้น',
            'type' => ProductType::Bundle->value,
            'price' => '1290.00',
            'is_active' => true,
            'components' => [
                [
                    'name' => 'ชุดนักศึกษา',
                    'option_groups' => [
                        ['key' => 'shirt_gender', 'label' => 'เพศ / แบบเสื้อ', 'values' => ['ชาย', 'หญิง']],
                        ['key' => 'bottom_type', 'label' => 'กางเกง / กระโปรง', 'values' => ['กางเกง', 'กระโปรง']],
                        ['key' => 'shirt_size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L', 'XL', '2XL']],
                        ['key' => 'bottom_size', 'label' => 'ไซส์กางเกง/กระโปรง', 'values' => ['S', 'M', 'L', 'XL', '2XL']],
                    ],
                ],
                [
                    'name' => 'ชุดเฟรชชี่',
                    'option_groups' => [
                        ['key' => 'shirt_size', 'label' => 'ไซส์เสื้อ', 'values' => ['S', 'M', 'L', 'XL', '2XL']],
                        ['key' => 'bottom_size', 'label' => 'ไซส์กางเกง', 'values' => ['S', 'M', 'L', 'XL', '2XL']],
                    ],
                ],
                [
                    'name' => 'เสื้อกิจกรรม',
                    'option_groups' => [
                        ['key' => 'size', 'label' => 'ไซส์', 'values' => ['S', 'M', 'L', 'XL', '2XL']],
                    ],
                ],
                [
                    'name' => 'เครื่องหมาย',
                    'option_groups' => [
                        ['key' => 'belt_size', 'label' => 'ไซส์เข็มขัด', 'values' => ['28', '30', '32', '34', '36', '38']],
                    ],
                ],
            ],
        ]);

        $this->upsertRate($shipping, [
            'name' => 'ทั่วประเทศ',
            'amount' => '50.00',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->upsertRate($shipping, [
            'name' => 'พื้นที่ห่างไกล',
            'amount' => '80.00',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->upsertOpenRound($booking, [$shirt, $pants, $combo]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertProduct(CatalogService $catalog, string $slug, array $payload): Product
    {
        $product = Product::query()->where('slug', $slug)->first()
            ?? Product::query()->where('name', $payload['name'])->first();

        $product = $product === null
            ? $catalog->create($payload)
            : $catalog->update($product, $payload);

        $product->update(['slug' => $slug]);

        return $product->fresh(['optionGroups.values', 'components.optionGroups.values']);
    }

    /**
     * @param  array{name: string, amount: string, is_active: bool, sort_order: int}  $payload
     */
    private function upsertRate(ShippingRateService $shipping, array $payload): ShippingRate
    {
        $rate = ShippingRate::query()->where('name', $payload['name'])->first();

        return $rate === null
            ? $shipping->create($payload)
            : $shipping->update($rate, $payload);
    }

    /**
     * @param  list<Product>  $products
     */
    private function upsertOpenRound(BookingRoundService $booking, array $products): BookingRound
    {
        $payload = [
            'name' => 'รอบเปิดจองชุดเฟรชชี่',
            'starts_at' => now()->subDay()->toDateTimeString(),
            'ends_at' => now()->addMonths(3)->toDateTimeString(),
            'is_enabled' => true,
            'product_ids' => collect($products)->pluck('id')->all(),
        ];

        $round = BookingRound::query()->where('name', $payload['name'])->first();

        return $round === null
            ? $booking->create($payload)
            : $booking->update($round, $payload);
    }
}
