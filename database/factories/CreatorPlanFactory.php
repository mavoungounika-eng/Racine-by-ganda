<?php

namespace Database\Factories;

use App\Models\CreatorPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreatorPlanFactory extends Factory
{
    protected $model = CreatorPlan::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->word(),
            'name' => fake()->words(2, true),
            'price' => fake()->randomFloat(2, 0, 20000),
            'annual_price' => null,
            'billing_cycle' => 'monthly',
            'is_active' => true,
            'description' => fake()->sentence(),
            'features' => [],
        ];
    }
}



