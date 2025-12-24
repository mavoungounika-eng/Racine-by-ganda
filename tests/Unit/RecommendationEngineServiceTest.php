<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Alerts\FinancialAlertService;
use App\Services\BI\AdvancedKpiService;
use App\Services\Decision\ChurnPredictionService;
use App\Services\Decision\CreatorDecisionScoreService;
use App\Services\Decision\RecommendationEngineService;
use App\Services\Risk\CreatorRiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - RecommendationEngineService
 * 
 * Phase 7.3 - Tests du moteur de recommandations
 */
class RecommendationEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecommendationEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $kpiService = new AdvancedKpiService();
        $riskService = new CreatorRiskAssessmentService();
        $alertService = new FinancialAlertService($kpiService, new \App\Services\BI\AdminFinancialDashboardService());
        
        $this->service = new RecommendationEngineService(
            $riskService,
            $alertService
        );
    }

    /** @test */
    public function it_generates_recommendations_for_creator()
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

        $result = $this->service->generateRecommendations($creator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertIsArray($result['recommendations']);
        $this->assertGreaterThan(0, $result['total_count']);
    }

    /** @test */
    public function it_generates_critical_recommendation_for_high_risk()
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

        $result = $this->service->generateRecommendations($creator);

        $criticalRecommendations = array_filter($result['recommendations'], function ($rec) {
            return $rec['priority'] === 'critical';
        });

        $this->assertNotEmpty($criticalRecommendations);
    }

    /** @test */
    public function it_includes_justification_for_each_recommendation()
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

        $result = $this->service->generateRecommendations($creator);

        foreach ($result['recommendations'] as $recommendation) {
            $this->assertArrayHasKey('action', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('justification', $recommendation);
            $this->assertNotEmpty($recommendation['justification']);
        }
    }

    /** @test */
    public function it_sorts_recommendations_by_priority()
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

        $result = $this->service->generateRecommendations($creator);

        $priorities = array_column($result['recommendations'], 'priority');
        $priorityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        
        for ($i = 0; $i < count($priorities) - 1; $i++) {
            $currentPriority = $priorityOrder[$priorities[$i]] ?? 0;
            $nextPriority = $priorityOrder[$priorities[$i + 1]] ?? 0;
            $this->assertGreaterThanOrEqual($nextPriority, $currentPriority);
        }
    }

    /** @test */
    public function it_handles_creator_with_no_data()
    {
        $creator = CreatorProfile::factory()->create([
            'is_active' => false,
            'status' => 'pending',
        ]);

        $result = $this->service->generateRecommendations($creator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        // Devrait quand même générer des recommandations basées sur les données disponibles
    }
}

