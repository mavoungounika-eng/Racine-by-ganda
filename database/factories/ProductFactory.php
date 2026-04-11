<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);
        
        return [
            'category_id' => Category::factory(), // Créer une catégorie par défaut
            'collection_id' => null,
            'user_id' => User::factory(), // Créer un utilisateur par défaut
            'product_type' => 'brand', // Enum: 'brand' ou 'marketplace' (voir migration 2025_12_06_120000)
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 1000, 50000),
            'stock' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'main_image' => null,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Set a specific stock level.
     */
    public function withStock(int $stock): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
        ]);
    }
}

