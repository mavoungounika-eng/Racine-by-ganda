<?php

namespace Modules\ERP\Database\Factories;

use Modules\ERP\Models\ErpSupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\ERP\Models\ErpSupplier>
 */
class ErpSupplierFactory extends Factory
{
    protected $model = ErpSupplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'tax_id' => $this->faker->numerify('##########'),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
