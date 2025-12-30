<?php

namespace Database\Factories;

use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OauthAccount>
 */
class OauthAccountFactory extends Factory
{
    protected $model = OauthAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['google', 'apple', 'facebook']),
            'provider_user_id' => fake()->unique()->uuid(),
            'provider_email' => fake()->optional()->safeEmail(),
            'provider_name' => fake()->name(),
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'is_primary' => false,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that this is the primary OAuth account.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that this is a Google account.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'google',
            'provider_user_id' => 'google-' . fake()->unique()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that this is an Apple account.
     */
    public function apple(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'apple',
            'provider_user_id' => 'apple-' . fake()->unique()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that this is a Facebook account.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'facebook',
            'provider_user_id' => 'facebook-' . fake()->unique()->numerify('##########'),
        ]);
    }
}



