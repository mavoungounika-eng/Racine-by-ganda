<?php

namespace Database\Factories;

use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PosSaleFactory extends Factory
{
    protected $model = PosSale::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'order_id' => Order::factory(),
            'machine_id' => 'MAC-' . $this->faker->numberBetween(100, 999),
            'session_id' => PosSession::factory(),
            'total_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'payment_method' => 'cash',
            'status' => 'pending',
            'created_by' => User::factory(),
            'customer_id' => null,
        ];
    }

    public function finalized()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);
    }
}
