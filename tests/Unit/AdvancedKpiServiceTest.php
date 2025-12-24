<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Services\BI\AdvancedKpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - AdvancedKpiService
 * 
 * Phase 6.2 - Tests des KPI avancés
 */
class AdvancedKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdvancedKpiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdvancedKpiService();
    }

    /** @test */
    public function it_calculates_churn_rate_with_no_data()
    {
        $churnRate = $this->service->calculateChurnRate('month');
        
        $this->assertEquals(0.0, $churnRate);
    }

    /** @test */
    public function it_calculates_churn_rate_correctly()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // 10 abonnements actifs au début du mois dernier
        for ($i = 0; $i < 10; $i++) {
            $creator = CreatorProfile::factory()->create();
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now()->subMonths(2),
            ]);
        }

        // 2 abonnements annulés le mois dernier
        for ($i = 0; $i < 2; $i++) {
            $creator = CreatorProfile::factory()->create();
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'canceled',
                'started_at' => now()->subMonths(2),
                'canceled_at' => now()->subMonth(),
            ]);
        }

        $churnRate = $this->service->calculateChurnRate('month');
        
        // Churn = 2 / 10 = 20%
        $this->assertGreaterThanOrEqual(15, $churnRate);
        $this->assertLessThanOrEqual(25, $churnRate);
    }

    /** @test */
    public function it_calculates_arpu_correctly()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // 5 créateurs payants
        for ($i = 0; $i < 5; $i++) {
            $creator = CreatorProfile::factory()->create();
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'active',
            ]);
        }

        $arpu = $this->service->calculateArpu();
        
        // ARPU = 25000 / 5 = 5000
        $this->assertEquals(5000.0, $arpu);
    }

    /** @test */
    public function it_calculates_arpu_with_no_paying_creators()
    {
        $arpu = $this->service->calculateArpu();
        
        $this->assertEquals(0.0, $arpu);
    }

    /** @test */
    public function it_calculates_ltv_correctly()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // Créer des abonnements annulés avec différentes durées
        $durations = [3, 6, 9, 12]; // mois
        foreach ($durations as $months) {
            $creator = CreatorProfile::factory()->create();
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'canceled',
                'started_at' => now()->subMonths($months + 1),
                'canceled_at' => now()->subMonths(1),
            ]);
        }

        $ltv = $this->service->calculateLtv();
        
        // LTV = ARPU × Durée moyenne
        // Durée moyenne = (3 + 6 + 9 + 12) / 4 = 7.5 mois
        // ARPU = 5000
        // LTV ≈ 5000 × 7.5 = 37500
        $this->assertGreaterThan(30000, $ltv);
        $this->assertLessThan(50000, $ltv);
    }

    /** @test */
    public function it_calculates_average_subscription_duration()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // Créer des abonnements annulés avec différentes durées
        $durations = [3, 6, 9];
        foreach ($durations as $months) {
            $creator = CreatorProfile::factory()->create();
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'canceled',
                'started_at' => now()->subMonths($months + 1),
                'canceled_at' => now()->subMonths(1),
            ]);
        }

        $averageDuration = $this->service->calculateAverageSubscriptionDuration();
        
        // Durée moyenne = (3 + 6 + 9) / 3 = 6 mois
        $this->assertGreaterThan(5, $averageDuration);
        $this->assertLessThan(7, $averageDuration);
    }

    /** @test */
    public function it_handles_empty_data_for_duration()
    {
        // Aucun abonnement annulé, devrait utiliser les abonnements actifs
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        
        $creator = CreatorProfile::factory()->create();
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now()->subMonths(3),
        ]);

        $averageDuration = $this->service->calculateAverageSubscriptionDuration();
        
        $this->assertGreaterThan(0, $averageDuration);
    }
}



