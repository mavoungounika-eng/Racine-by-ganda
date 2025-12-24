<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Decision\ChurnPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - ChurnPredictionService
 * 
 * Phase 7.2 - Tests de prédiction de churn
 */
class ChurnPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChurnPredictionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChurnPredictionService();
    }

    /** @test */
    public function it_predicts_churn_for_creator()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $result = $this->service->predictChurn($creator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('churn_probability', $result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('classification', $result);
        $this->assertArrayHasKey('factors', $result);
        
        $this->assertGreaterThanOrEqual(0, $result['churn_probability']);
        $this->assertLessThanOrEqual(100, $result['churn_probability']);
        $this->assertContains($result['classification'], ['low', 'medium', 'high']);
    }

    /** @test */
    public function it_predicts_high_churn_for_unpaid_subscription()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'status' => 'unpaid',
        ]);

        $result = $this->service->predictChurn($creator);

        $this->assertEquals('high', $result['classification']);
        $this->assertGreaterThan(50, $result['churn_probability']);
        $this->assertContains('Abonnement unpaid', $result['factors']);
    }

    /** @test */
    public function it_predicts_low_churn_for_stable_creator()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::factory()->create([
            'creator_profile_id' => $creator->id,
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now()->subMonths(12), // Abonnement ancien
        ]);

        $result = $this->service->predictChurn($creator);

        // Avec un abonnement stable, le risque devrait être faible
        $this->assertLessThan(50, $result['churn_probability']);
    }

    /** @test */
    public function it_handles_creator_with_no_subscription()
    {
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        $result = $this->service->predictChurn($creator);

        $this->assertIsArray($result);
        $this->assertContains('Aucun abonnement actif', $result['factors']);
        $this->assertGreaterThan(20, $result['risk_score']);
    }

    /** @test */
    public function it_includes_failed_payments_in_prediction()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Créer plusieurs abonnements avec statut unpaid (simule des paiements échoués)
        for ($i = 0; $i < 3; $i++) {
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_id' => $user->id,
                'creator_plan_id' => $plan->id,
                'status' => 'unpaid',
                'updated_at' => now()->subDays($i * 10),
            ]);
        }

        $result = $this->service->predictChurn($creator);

        $this->assertGreaterThan(40, $result['risk_score']);
        $this->assertContains('paiements échoués', implode(' ', $result['factors']));
    }
}



