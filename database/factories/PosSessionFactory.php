<?php

namespace Database\Factories;

use App\Models\PosSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PosSession>
 */
class PosSessionFactory extends Factory
{
    protected $model = PosSession::class;

    public function definition(): array
    {
        return [
            'machine_id' => (string) Str::uuid(),
            'opened_by' => User::factory(),
            'opened_at' => now(),
            'opening_cash' => $this->faker->randomFloat(2, 0, 500000),
            'status' => PosSession::STATUS_OPEN,
            'closed_at' => null,
            'closing_cash' => null,
            'expected_cash' => null,
            'cash_difference' => null,
            'closed_by' => null,
            'notes' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes): array {
            $expected = (float) ($attributes['expected_cash'] ?? $attributes['opening_cash'] ?? 0);
            $closing = (float) ($attributes['closing_cash'] ?? $expected);

            return [
                'status' => PosSession::STATUS_CLOSED,
                'closed_at' => now(),
                'closing_cash' => $closing,
                'expected_cash' => $expected,
                'cash_difference' => $closing - $expected,
                'closed_by' => User::factory(),
            ];
        });
    }
}
