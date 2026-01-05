<?php

namespace Database\Factories;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreatorProfileFactory extends Factory
{
    protected $model = CreatorProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'brand_name' => fake()->company(),
            'slug' => fake()->slug(),
            'bio' => fake()->paragraph(),
            'logo_path' => null,
            'banner_path' => null,
            'location' => fake()->city(),
            'website' => fake()->url(),
            'instagram_url' => null,
            'tiktok_url' => null,
            'type' => 'individual',
            'legal_status' => 'individual',
            'registration_number' => null,
            'payout_method' => 'bank',
            'payout_details' => [],
            'status' => 'active',
            'is_verified' => false,
            'is_active' => true,
            'quality_score' => null,
            'completeness_score' => null,
            'performance_score' => null,
            'overall_score' => null,
            'last_score_calculated_at' => null,
        ];
    }

    /**
     * ✅ Profil avec abonnement actif (OBLIGATOIRE POUR TESTS)
     */
    public function withActiveSubscription(): static
    {
        return $this->afterCreating(function (CreatorProfile $profile) {
            $plan = CreatorPlan::first()
                ?? CreatorPlan::factory()->create();

            CreatorSubscription::create([
                'creator_profile_id'     => $profile->id,
                'creator_plan_id'        => $plan->id,
                'status'                 => 'active',

                // Champs Stripe NON NULL
                'stripe_subscription_id' => 'sub_test_' . uniqid(),
                'stripe_customer_id'     => 'cus_test_' . uniqid(),
                'stripe_price_id'        => 'price_test_' . uniqid(),

                'starts_at' => now()->subMonth(),
                'ends_at'   => null,
            ]);
        });
    }
}
