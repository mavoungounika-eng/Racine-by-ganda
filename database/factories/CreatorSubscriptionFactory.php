<?php

namespace Database\Factories;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreatorSubscriptionFactory extends Factory
{
    protected $model = CreatorSubscription::class;

    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 year', 'now');
        $periodEnd = (clone $startedAt)->modify('+1 month');

        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'creator_id' => User::factory(),
            'creator_plan_id' => CreatorPlan::factory(),
            'stripe_subscription_id' => 'sub_' . fake()->unique()->bothify('##########'),
            'stripe_customer_id' => 'cus_' . fake()->unique()->bothify('##########'),
            'stripe_price_id' => 'price_' . fake()->unique()->bothify('##########'),
            'status' => 'active',
            'current_period_start' => $startedAt,
            'current_period_end' => $periodEnd,
            'started_at' => $startedAt,
            'ends_at' => null,
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'trial_start' => null,
            'trial_end' => null,
            'metadata' => [],
        ];
    }
}



