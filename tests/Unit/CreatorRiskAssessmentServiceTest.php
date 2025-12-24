<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Services\Risk\CreatorRiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - CreatorRiskAssessmentService
 * 
 * Phase 6.3 - Tests d'évaluation des risques
 */
class CreatorRiskAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreatorRiskAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreatorRiskAssessmentService();
    }

    /** @test */
    public function it_assesses_low_risk_creator()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $creator = CreatorProfile::factory()->create([
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
            'creator_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertEquals('low', $assessment['risk_level']);
        $this->assertEquals('monitor', $assessment['recommended_action']);
        $this->assertLessThan(30, $assessment['risk_score']);
    }

    /** @test */
    public function it_assesses_medium_risk_creator_with_past_due()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $creator = CreatorProfile::factory()->create([
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
            'creator_plan_id' => $plan->id,
            'status' => 'past_due',
        ]);

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertEquals('medium', $assessment['risk_level']);
        $this->assertEquals('notify', $assessment['recommended_action']);
        $this->assertContains('Abonnement past_due', $assessment['reasons']);
    }

    /** @test */
    public function it_assesses_high_risk_creator_with_unpaid()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::factory()->create([
            'creator_profile_id' => $creator->id,
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'onboarding_status' => 'failed',
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'unpaid',
        ]);

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertEquals('high', $assessment['risk_level']);
        $this->assertEquals('suspend', $assessment['recommended_action']);
        $this->assertGreaterThanOrEqual(60, $assessment['risk_score']);
        $this->assertContains('Abonnement unpaid', $assessment['reasons']);
    }

    /** @test */
    public function it_assesses_risk_with_incomplete_onboarding()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::factory()->create([
            'creator_profile_id' => $creator->id,
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'in_progress',
            'created_at' => now()->subDays(10), // > 7 jours
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertContains('Onboarding Stripe incomplet', $assessment['reasons']);
        $this->assertGreaterThan(0, $assessment['risk_score']);
    }

    /** @test */
    public function it_assesses_risk_with_no_stripe_account()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertContains('Aucun compte Stripe', $assessment['reasons']);
    }

    /** @test */
    public function it_assesses_risk_with_no_subscription()
    {
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::factory()->create([
            'creator_profile_id' => $creator->id,
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
        ]);

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertContains('Aucun abonnement actif', $assessment['reasons']);
    }

    /** @test */
    public function it_assesses_risk_with_failed_payments()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::factory()->create([
            'creator_profile_id' => $creator->id,
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
        ]);

        // Créer plusieurs abonnements avec statut unpaid (simule des paiements échoués)
        for ($i = 0; $i < 3; $i++) {
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'unpaid',
                'updated_at' => now()->subDays($i * 5),
            ]);
        }

        $assessment = $this->service->assessCreatorRisk($creator);

        $this->assertGreaterThan(0, $assessment['risk_score']);
    }
}



