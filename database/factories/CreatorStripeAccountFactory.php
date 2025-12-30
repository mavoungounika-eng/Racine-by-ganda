<?php

namespace Database\Factories;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreatorStripeAccountFactory extends Factory
{
    protected $model = CreatorStripeAccount::class;

    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'stripe_account_id' => 'acct_' . fake()->unique()->bothify('##########'),
            'account_type' => 'express',
            'onboarding_status' => 'complete',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
            'requirements_currently_due' => [],
            'requirements_eventually_due' => [],
            'capabilities' => [],
            'onboarding_link_url' => null,
            'onboarding_link_expires_at' => null,
            'last_synced_at' => now(),
        ];
    }
}



