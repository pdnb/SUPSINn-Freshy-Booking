<?php

namespace Database\Factories;

use App\Models\AdsBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdsBanner>
 */
class AdsBannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'image_path' => 'ads-banners/'.fake()->uuid().'.jpg',
            'url' => 'https://example.com/promo',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
