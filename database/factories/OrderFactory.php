<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address_id' => null,
            'promo_code_id' => null,
            'discount_amount' => 0,
            'shipping_method' => 'standard',
            'shipping_cost' => 2000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'card',
            'total_amount' => fake()->randomFloat(2, 10000, 100000),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_address' => fake()->address(),
            'qr_token' => null, // Généré automatiquement par le boot
            'order_number' => null, // Généré automatiquement par le boot
        ];
    }
}

