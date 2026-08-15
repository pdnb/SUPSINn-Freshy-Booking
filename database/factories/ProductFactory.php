<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => 'product-'.Str::lower(Str::random(8)),
            'description' => null,
            'type' => ProductType::Simple,
            'price' => '199.00',
            'is_active' => true,
        ];
    }

    public function bundle(): static
    {
        return $this->state(fn () => ['type' => ProductType::Bundle]);
    }
}
