<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Action\ActionProposalService;
use App\Services\Alerts\FinancialAlertService;
use App\Services\BI\AdvancedKpiService;
use App\Services\BI\AdminFinancialDashboardService;
use App\Services\Decision\ChurnPredictionService;
use App\Services\Decision\CreatorDecisionScoreService;
use App\Services\Risk\CreatorRiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - ActionProposalService
 * 
 * Phase 8.1 - Tests de proposition d'actions
 */
class ActionProposalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActionProposalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $kpiService = new AdvancedKpiService();
        $riskService = new CreatorRiskAssessmentService();
        $alertService = new FinancialAlertService($kpiService, new AdminFinancialDashboardService());
        $decisionScoreService = new CreatorDecisionScoreService($kpiService, $riskService);
        $churnPredictionService = new ChurnPredictionService();
        
        $this->service = new ActionProposalService(
            $decisionScoreService,
            $churnPredictionService,
            $riskService,
            $alertService
        );
    }

    /** @test */
    public function it_proposes_actions_for_creator()
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

        $result = $this->service->proposeActions($creator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('proposals', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertIsArray($result['proposals']);
        $this->assertGreaterThan(0, $result['total_count']);
    }

    /** @test */
    public function it_proposes_suspension_for_high_risk_creator()
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

        $result = $this->service->proposeActions($creator);

        $suspensionProposal = collect($result['proposals'])->firstWhere('action', 'PROPOSE_SUSPENSION');
        
        $this->assertNotNull($suspensionProposal);
        $this->assertEquals('high', $suspensionProposal['risk_level']);
    }

    /** @test */
    public function it_proposes_monitor_when_no_critical_actions()
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
            'started_at' => now()->subMonths(12),
        ]);

        $result = $this->service->proposeActions($creator);

        // Devrait proposer au moins MONITOR
        $this->assertGreaterThan(0, $result['total_count']);
        $monitorProposal = collect($result['proposals'])->firstWhere('action', 'MONITOR');
        $this->assertNotNull($monitorProposal);
    }

    /** @test */
    public function it_includes_justification_for_each_proposal()
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
            'status' => 'past_due',
        ]);

        $result = $this->service->proposeActions($creator);

        foreach ($result['proposals'] as $proposal) {
            $this->assertArrayHasKey('justification', $proposal);
            $this->assertNotEmpty($proposal['justification']);
            $this->assertArrayHasKey('action', $proposal);
            $this->assertArrayHasKey('target_type', $proposal);
            $this->assertArrayHasKey('target_id', $proposal);
        }
    }

    /** @test */
    public function it_sorts_proposals_by_priority()
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

        $result = $this->service->proposeActions($creator);

        // Les actions à haut risque devraient être en premier
        if (count($result['proposals']) > 1) {
            $firstProposal = $result['proposals'][0];
            $this->assertGreaterThanOrEqual(50, $firstProposal['confidence'] ?? 0);
        }
    }
}



