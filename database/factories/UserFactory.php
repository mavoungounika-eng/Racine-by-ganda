<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        $clientRoleId = $this->resolveRoleId('client', 'Client', 'Client role');

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => $clientRoleId,
            'role' => 'client',
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
        $adminRoleId = $this->resolveRoleId('admin', 'Admin', 'Administrator role');

        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'role_id' => $adminRoleId,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function resolveRoleId(string $slug, string $name, string $description): ?int
    {
        try {
            if (!Schema::hasTable('roles')) {
                return null;
            }

            $role = \App\Models\Role::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                ]
            );

            return $role->id;
        } catch (\Throwable) {
            // Unit tests that only build models with make() may not have roles migrated.
            return null;
        }
    }
}
