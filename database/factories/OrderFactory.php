<?php

namespace Database\Factories;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'FR'.strtoupper(Str::random(8)),
            'tracking_token' => Str::random(40),
            'student_id' => '67011234567',
            'full_name' => fake()->name(),
            'faculty' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
            'major' => 'วิทยาการคอมพิวเตอร์',
            'phone' => '0812345678',
            'fulfillment' => FulfillmentMethod::Bookstore,
            'address' => null,
            'subtotal' => '350.00',
            'shipping_amount' => '0.00',
            'total' => '350.00',
            'status' => OrderStatus::PendingReview,
        ];
    }
}
