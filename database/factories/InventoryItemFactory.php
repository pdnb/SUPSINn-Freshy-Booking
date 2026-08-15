<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'choice_label' => 'ไซส์เสื้อ',
            'choice_value' => 'M',
            'on_hand' => 10,
            'threshold' => 2,
        ];
    }
}
