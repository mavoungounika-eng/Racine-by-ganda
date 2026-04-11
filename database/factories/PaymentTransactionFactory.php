<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => $this->faker->randomElement(['stripe', 'monetbil']),
            'order_id' => Order::factory(),
            'payment_ref' => 'PAY_' . $this->faker->unique()->numerify('########'),
            'transaction_id' => 'TXN_' . $this->faker->unique()->numerify('########'),
            'transaction_uuid' => $this->faker->uuid(),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => 'XAF',
            'status' => 'pending',
            'operator' => null,
            'phone' => null,
            'fee' => 0,
            'raw_payload' => null,
            'notified_at' => null,
        ];
    }
}
