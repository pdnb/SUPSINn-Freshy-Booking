<?php

namespace Database\Factories;

use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    public function definition(): array
    {
        $amount = '50.00';

        return [
            'name' => fake()->unique()->words(2, true),
            'amount' => $amount,
            'tiers' => [
                [
                    'min_qty' => 1,
                    'max_qty' => null,
                    'amount' => $amount,
                ],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
