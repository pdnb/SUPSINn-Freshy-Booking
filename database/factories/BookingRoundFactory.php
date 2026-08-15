<?php

namespace Database\Factories;

use App\Models\BookingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRound>
 */
class BookingRoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'is_enabled' => true,
        ];
    }
}
