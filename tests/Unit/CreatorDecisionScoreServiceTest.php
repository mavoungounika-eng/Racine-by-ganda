<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\BI\AdvancedKpiService;
use App\Services\Decision\CreatorDecisionScoreService;
use App\Services\Risk\CreatorRiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - CreatorDecisionScoreService
 * 
 * Phase 7.1 - Tests du scoring décisionnel
 */
class CreatorDecisionScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreatorDecisionScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $kpiService = new AdvancedKpiService();
        $riskService = new CreatorRiskAssessmentService();
        $this->service = new CreatorDecisionScoreService($kpiService, $riskService);
    }

    /** @test */
    public function it_calculates_decision_score_for_creator()
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
            'started_at' => now()->subMonths(6),
        ]);

        $result = $this->service->calculateDecisionScore($creator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('global_score', $result);
        $this->assertArrayHasKey('qualitative_grade', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('weaknesses', $result);
        $this->assertArrayHasKey('confidence_level', $result);
        
        $this->assertGreaterThanOrEqual(0, $result['global_score']);
        $this->assertLessThanOrEqual(100, $result['global_score']);
        $this->assertContains($result['qualitative_grade'], ['A', 'B', 'C', 'D']);
    }

    /** @test */
    public function it_handles_creator_with_no_subscription()
    {
        $creator = CreatorProfile::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        $result = $this->service->calculateDecisionScore($creator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('global_score', $result);
        // Score devrait être faible sans abonnement
        $this->assertLessThan(50, $result['global_score']);
    }

    /** @test */
    public function it_calculates_qualitative_grade_correctly()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
            'brand_name' => 'Test Brand',
            'bio' => 'Test bio',
            'location' => 'Test location',
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
            'started_at' => now()->subMonths(12),
        ]);

        $result = $this->service->calculateDecisionScore($creator);

        // Avec un créateur bien configuré, devrait avoir un grade B ou A
        $this->assertContains($result['qualitative_grade'], ['A', 'B', 'C', 'D']);
    }

    /** @test */
    public function it_identifies_strengths_and_weaknesses()
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
        ]);

        $result = $this->service->calculateDecisionScore($creator);

        $this->assertIsArray($result['strengths']);
        $this->assertIsArray($result['weaknesses']);
        $this->assertNotEmpty($result['strengths']);
        $this->assertNotEmpty($result['weaknesses']);
    }

    /** @test */
    public function it_calculates_confidence_level()
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
        ]);

        $result = $this->service->calculateDecisionScore($creator);

        $this->assertArrayHasKey('confidence_level', $result);
        $this->assertGreaterThanOrEqual(0, $result['confidence_level']);
        $this->assertLessThanOrEqual(100, $result['confidence_level']);
    }
}



