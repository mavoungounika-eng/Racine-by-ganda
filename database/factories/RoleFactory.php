<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Client', 'Créateur', 'Admin', 'Staff']),
            'slug' => $this->faker->unique()->randomElement(['client', 'createur', 'admin', 'staff']),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the role is a client.
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Client',
            'slug' => 'client',
            'description' => 'Client standard',
        ]);
    }

    /**
     * Indicate that the role is a creator.
     */
    public function creator(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Créateur',
            'slug' => 'createur',
            'description' => 'Créateur de contenu',
        ]);
    }

    /**
     * Indicate that the role is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrateur',
        ]);
    }

    /**
     * Indicate that the role is staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Staff',
            'slug' => 'staff',
            'description' => 'Membre de l\'équipe',
        ]);
    }
}
