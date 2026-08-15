<?php

namespace Database\Factories;

use App\Enums\SlipVerificationResult;
use App\Models\Order;
use App\Models\PaymentSlip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentSlip>
 */
class PaymentSlipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'path' => 'slips/example.jpg',
            'original_name' => 'slip.jpg',
            'checksum' => hash('sha256', Str::random(16)),
            'verifier_result' => SlipVerificationResult::Pass,
        ];
    }
}
