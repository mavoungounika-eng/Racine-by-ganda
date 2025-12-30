<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\CreatorSubscriptionInvoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\Analytics\BiMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests unitaires pour les calculs BI critiques
 * 
 * Module 7 - Analytics & BI
 * 
 * Vérifie la logique mathématique des KPIs :
 * - MRR
 * - ARR
 * - ARPU
 * - Churn
 * - LTV
 */
class BiMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BiMetricsService $biService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->biService = new BiMetricsService();
        Cache::flush();
    }

    /**
     * Test : Calcul MRR - Abonnements actifs uniquement
     */
    public function test_mrr_calculation_active_subscriptions_only(): void
    {
        // Créer des plans
        $plan1 = CreatorPlan::factory()->create(['price' => 10000, 'code' => 'official']);
        $plan2 = CreatorPlan::factory()->create(['price' => 20000, 'code' => 'premium']);
        $freePlan = CreatorPlan::factory()->create(['price' => 0, 'code' => 'free']);
        
        // Créer des abonnements actifs
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $plan1->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $plan2->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        // Abonnement gratuit (ne doit pas compter)
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $freePlan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        // Abonnement annulé (ne doit pas compter)
        CreatorSubscription::factory()->create([
            'status' => 'canceled',
            'creator_plan_id' => $plan1->id,
            'started_at' => now()->subMonth(),
            'canceled_at' => now()->subWeek(),
        ]);
        
        $mrr = $this->biService->calculateMRR();
        
        // MRR attendu : 10000 + 20000 = 30000
        $this->assertEquals(30000.00, $mrr);
    }

    /**
     * Test : Calcul ARR = MRR × 12
     */
    public function test_arr_calculation_is_mrr_times_12(): void
    {
        $plan = CreatorPlan::factory()->create(['price' => 10000, 'code' => 'official']);
        
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        $mrr = $this->biService->calculateMRR();
        $arr = $this->biService->calculateARR();
        
        // ARR = MRR × 12
        $this->assertEquals($mrr * 12, $arr);
        $this->assertEquals(120000.00, $arr);
    }

    /**
     * Test : Calcul ARPU = MRR / Nombre de créateurs payants
     */
    public function test_arpu_calculation(): void
    {
        $plan = CreatorPlan::factory()->create(['price' => 10000, 'code' => 'official']);
        
        $creator1 = CreatorProfile::factory()->create();
        $creator2 = CreatorProfile::factory()->create();
        
        // 2 créateurs avec le même plan
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator1->id,
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator2->id,
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        $mrr = $this->biService->calculateMRR();
        $arpu = $this->biService->calculateARPU();
        
        // ARPU = MRR / 2 créateurs = 10000 / 2 = 5000
        $this->assertEquals(5000.00, $arpu);
        $this->assertEquals($mrr / 2, $arpu);
    }

    /**
     * Test : Calcul Churn Rate
     */
    public function test_churn_rate_calculation(): void
    {
        $plan = CreatorPlan::factory()->create(['price' => 10000, 'code' => 'official']);
        
        // 10 abonnements actifs au début du mois précédent
        for ($i = 0; $i < 10; $i++) {
            CreatorSubscription::factory()->create([
                'status' => 'active',
                'creator_plan_id' => $plan->id,
                'started_at' => now()->subMonths(2),
                'ends_at' => null,
            ]);
        }
        
        // 2 abonnements annulés le mois précédent
        CreatorSubscription::factory()->create([
            'status' => 'canceled',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonths(2),
            'canceled_at' => now()->subMonth()->addDays(5),
        ]);
        
        CreatorSubscription::factory()->create([
            'status' => 'canceled',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonths(2),
            'canceled_at' => now()->subMonth()->addDays(10),
        ]);
        
        $churnRate = $this->biService->calculateChurnRate('month');
        
        // Churn = (2 / 10) × 100 = 20%
        $this->assertEquals(20.00, $churnRate);
    }

    /**
     * Test : Calcul LTV = ARPU × Durée moyenne
     */
    public function test_ltv_calculation(): void
    {
        $plan = CreatorPlan::factory()->create(['price' => 10000, 'code' => 'official']);
        
        $creator = CreatorProfile::factory()->create();
        
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        // Créer un abonnement annulé après 6 mois pour calculer la durée moyenne
        CreatorSubscription::factory()->create([
            'status' => 'canceled',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonths(6),
            'canceled_at' => now(),
        ]);
        
        $arpu = $this->biService->calculateARPU();
        $avgDuration = $this->biService->calculateAverageSubscriptionDuration();
        $ltv = $this->biService->calculateLTV();
        
        // LTV = ARPU × Durée moyenne
        $this->assertEquals($arpu * $avgDuration, $ltv);
    }

    /**
     * Test : MRR avec abonnements expirés exclus
     */
    public function test_mrr_excludes_expired_subscriptions(): void
    {
        $plan = CreatorPlan::factory()->create(['price' => 10000, 'code' => 'official']);
        
        // Abonnement actif
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        // Abonnement expiré (ne doit pas compter)
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonths(2),
            'ends_at' => now()->subWeek(), // Expiré
        ]);
        
        $mrr = $this->biService->calculateMRR();
        
        // MRR attendu : uniquement l'abonnement actif = 10000
        $this->assertEquals(10000.00, $mrr);
    }

    /**
     * Test : ARPU avec zéro créateur payant retourne 0
     */
    public function test_arpu_returns_zero_when_no_paying_creators(): void
    {
        // Pas d'abonnements payants
        $arpu = $this->biService->calculateARPU();
        
        $this->assertEquals(0.0, $arpu);
    }

    /**
     * Test : Churn Rate avec zéro abonnement actif retourne 0
     */
    public function test_churn_rate_returns_zero_when_no_active_subscriptions(): void
    {
        // Pas d'abonnements actifs
        $churnRate = $this->biService->calculateChurnRate('month');
        
        $this->assertEquals(0.0, $churnRate);
    }

    /**
     * Test : Vérification cohérence MRR/ARR
     */
    public function test_mrr_arr_consistency(): void
    {
        $plan = CreatorPlan::factory()->create(['price' => 15000, 'code' => 'official']);
        
        CreatorSubscription::factory()->create([
            'status' => 'active',
            'creator_plan_id' => $plan->id,
            'started_at' => now()->subMonth(),
            'ends_at' => null,
        ]);
        
        $mrr = $this->biService->calculateMRR();
        $arr = $this->biService->calculateARR();
        
        // Vérifier que ARR = MRR × 12
        $this->assertEquals($mrr * 12, $arr);
        $this->assertEquals(15000.00, $mrr);
        $this->assertEquals(180000.00, $arr);
    }
}

