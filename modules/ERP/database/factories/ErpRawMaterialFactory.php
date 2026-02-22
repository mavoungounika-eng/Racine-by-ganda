<?php

namespace Modules\ERP\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpSupplier;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\ERP\Models\ErpRawMaterial>
 */
class ErpRawMaterialFactory extends Factory
{
    protected $model = ErpRawMaterial::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'reference' => 'RM-' . strtoupper($this->faker->unique()->bothify('??###')),
            'unit' => 'meter',
            'current_stock' => $this->faker->randomFloat(2, 0, 500),
            'min_stock_alert' => $this->faker->randomFloat(2, 1, 50),
            'unit_price' => $this->faker->randomFloat(2, 100, 10000),
            'supplier_id' => ErpSupplier::factory(),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
