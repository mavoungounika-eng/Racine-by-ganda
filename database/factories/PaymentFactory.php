<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'stripe',
            'provider_payment_id' => null,
            'status' => 'pending',
            'amount' => fake()->randomFloat(2, 10000, 100000),
            'currency' => 'XAF',
            'channel' => 'card',
            'customer_phone' => null,
            'external_reference' => 'cs_test_' . fake()->uuid(),
            'metadata' => [],
            'payload' => [],
            'paid_at' => null,
        ];
    }
}

