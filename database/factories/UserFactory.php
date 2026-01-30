<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get or create the client role (FK constraint)
        $clientRole = \App\Models\Role::where('slug', 'client')->first();
        if (!$clientRole) {
            $clientRole = \App\Models\Role::create([
                'name' => 'Client',
                'slug' => 'client',
                'description' => 'Client role',
            ]);
        }

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => $clientRole->id, // Default to client role
            'phone' => fake()->optional()->phoneNumber(),
            'is_admin' => false,
            'status' => 'active',
            'auth_version' => 1, // Default auth_version
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        $adminRole = \App\Models\Role::where('slug', 'admin')->first();
        if (!$adminRole) {
            $adminRole = \App\Models\Role::create([
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator role',
            ]);
        }

        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
    }
}
