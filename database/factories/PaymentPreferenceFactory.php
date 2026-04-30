<?php

namespace Database\Factories;

use App\Models\CreatorProfile;
use App\Models\PaymentPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentPreference>
 */
class PaymentPreferenceFactory extends Factory
{
    protected $model = PaymentPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'stripe_secret_key' => 'sk_test_' . $this->faker->sha256(),
            'stripe_publishable_key' => 'pk_test_' . $this->faker->sha256(),
            'momo_provider' => 'monetbil',
            'momo_api_key' => 'test_api_key',
            'payment_connection_status' => 'connected',
            'last_connection_test_at' => now(),
            'notify_email' => true,
            'notify_sms' => false,
            'notify_push' => true,
            'tax_info_completed' => false,
        ];
    }
}
