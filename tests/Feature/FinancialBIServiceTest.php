<?php

namespace Tests\Feature;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Financial\FinancialDashboardService;
use App\Services\Financial\StrategicMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests BI pour les services financiers
 * 
 * Phase 6.7 - Tests BI
 */
class FinancialBIServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialDashboardService $financialService;
    protected StrategicMetricsService $strategicService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->financialService = app(FinancialDashboardService::class);
        $this->strategicService = app(StrategicMetricsService::class);
    }

    /**
     * Test : MRR calculé correctement
     */
    public function test_mrr_calculated_correctly(): void
    {
        // Créer des plans
        $planOfficial = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $planPremium = CreatorPlan::create([
            'code' => 'premium',
            'name' => 'Créateur Premium',
            'price' => 15000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        // Créer des créateurs avec abonnements actifs
        $user1 = User::factory()->create(['role' => 'createur']);
        $creator1 = CreatorProfile::create([
            'user_id' => $user1->id,
            'brand_name' => 'Creator 1',
            'slug' => 'creator-1',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorSubscription::create([
            'creator_profile_id' => $creator1->id,
            'creator_id' => $user1->id,
            'creator_plan_id' => $planOfficial->id,
            'stripe_subscription_id' => 'sub_test_1',
            'stripe_customer_id' => 'cus_test_1',
            'stripe_price_id' => 'price_test_1',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $user2 = User::factory()->create(['role' => 'createur']);
        $creator2 = CreatorProfile::create([
            'user_id' => $user2->id,
            'brand_name' => 'Creator 2',
            'slug' => 'creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorSubscription::create([
            'creator_profile_id' => $creator2->id,
            'creator_id' => $user2->id,
            'creator_plan_id' => $planPremium->id,
            'stripe_subscription_id' => 'sub_test_2',
            'stripe_customer_id' => 'cus_test_2',
            'stripe_price_id' => 'price_test_2',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Calculer le MRR
        $mrr = $this->financialService->calculateMRR();

        // MRR attendu : 5000 + 15000 = 20000
        $this->assertEquals(20000, $mrr);
    }

    /**
     * Test : ARR = MRR × 12
     */
    public function test_arr_equals_mrr_times_twelve(): void
    {
        $mrr = $this->financialService->calculateMRR();
        $arr = $this->financialService->calculateARR();

        $this->assertEquals($mrr * 12, $arr);
    }

    /**
     * Test : Churn Rate calculé correctement
     */
    public function test_churn_rate_calculated_correctly(): void
    {
        // Créer 10 abonnements actifs
        $plan = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create(['role' => 'createur']);
            $creator = CreatorProfile::create([
                'user_id' => $user->id,
                'brand_name' => "Creator {$i}",
                'slug' => "creator-{$i}",
                'is_active' => true,
                'status' => 'active',
            ]);

            CreatorSubscription::create([
                'creator_profile_id' => $creator->id,
                'creator_id' => $user->id,
                'creator_plan_id' => $plan->id,
                'stripe_subscription_id' => "sub_test_{$i}",
                'stripe_customer_id' => "cus_test_{$i}",
                'stripe_price_id' => 'price_test_1',
                'status' => 'active',
                'current_period_start' => now()->subMonth(),
                'current_period_end' => now()->addMonth(),
                'started_at' => now()->subMonth(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        // Annuler 2 abonnements ce mois
        $subscriptions = CreatorSubscription::where('status', 'active')->limit(2)->get();
        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);
        }

        // Churn Rate attendu : (2 / 10) × 100 = 20%
        $churnRate = $this->strategicService->calculateChurnRate();
        $this->assertEquals(20, $churnRate);
    }

    /**
     * Test : Requêtes optimisées avec index
     */
    public function test_queries_use_indexes(): void
    {
        DB::enableQueryLog();

        // Exécuter une requête qui devrait utiliser les index
        $this->financialService->getTotalActiveSubscriptions();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Vérifier que la requête utilise WHERE sur status (indexé)
        $this->assertStringContainsString('status', $lastQuery['query']);
        
        DB::disableQueryLog();
    }

    /**
     * Test : Données cohérentes (pas de doublons)
     */
    public function test_no_duplicate_subscriptions(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creator = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $plan = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        // Créer un abonnement
        CreatorSubscription::create([
            'creator_profile_id' => $creator->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'stripe_subscription_id' => 'sub_test_unique',
            'stripe_customer_id' => 'cus_test_unique',
            'stripe_price_id' => 'price_test_unique',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Tenter de créer un doublon (doit échouer à cause de la contrainte unique)
        try {
            CreatorSubscription::create([
                'creator_profile_id' => $creator->id,
                'creator_id' => $user->id,
                'creator_plan_id' => $plan->id,
                'stripe_subscription_id' => 'sub_test_unique', // Même ID
                'stripe_customer_id' => 'cus_test_unique_2',
                'stripe_price_id' => 'price_test_unique_2',
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);

            $this->fail('Un doublon a été créé alors que la contrainte unique devrait l\'empêcher');
        } catch (\Exception $e) {
            // Exception attendue (contrainte unique)
            $this->assertTrue(true);
        }

        // Vérifier qu'il n'y a qu'un seul abonnement
        $count = CreatorSubscription::where('stripe_subscription_id', 'sub_test_unique')->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test : Dashboard stable avec données volumineuses (simulation)
     */
    public function test_dashboard_stable_with_large_dataset(): void
    {
        // Créer 100 abonnements (simulation de charge)
        $plan = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 100; $i++) {
            $user = User::factory()->create(['role' => 'createur']);
            $creator = CreatorProfile::create([
                'user_id' => $user->id,
                'brand_name' => "Creator {$i}",
                'slug' => "creator-{$i}",
                'is_active' => true,
                'status' => 'active',
            ]);

            CreatorSubscription::create([
                'creator_profile_id' => $creator->id,
                'creator_id' => $user->id,
                'creator_plan_id' => $plan->id,
                'stripe_subscription_id' => "sub_test_{$i}",
                'stripe_customer_id' => "cus_test_{$i}",
                'stripe_price_id' => 'price_test_1',
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        // Mesurer le temps d'exécution
        $start = microtime(true);
        $metrics = $this->financialService->getDashboardMetrics();
        $end = microtime(true);

        $executionTime = ($end - $start) * 1000; // en millisecondes

        // Vérifier que les métriques sont calculées
        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('subscriptions', $metrics);
        $this->assertArrayHasKey('creators', $metrics);

        // Vérifier que le temps d'exécution est raisonnable (< 2 secondes)
        $this->assertLessThan(2000, $executionTime, 'Le dashboard doit se charger en moins de 2 secondes');

        // Vérifier que le MRR est correct (100 × 5000 = 500000)
        $this->assertEquals(500000, $metrics['revenue']['mrr']);
    }

    /**
     * Test : ARPU calculé correctement
     */
    public function test_arpu_calculated_correctly(): void
    {
        $plan = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        // Créer 5 créateurs payants
        for ($i = 1; $i <= 5; $i++) {
            $user = User::factory()->create(['role' => 'createur']);
            $creator = CreatorProfile::create([
                'user_id' => $user->id,
                'brand_name' => "Creator {$i}",
                'slug' => "creator-{$i}",
                'is_active' => true,
                'status' => 'active',
            ]);

            CreatorSubscription::create([
                'creator_profile_id' => $creator->id,
                'creator_id' => $user->id,
                'creator_plan_id' => $plan->id,
                'stripe_subscription_id' => "sub_test_{$i}",
                'stripe_customer_id' => "cus_test_{$i}",
                'stripe_price_id' => 'price_test_1',
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        // ARPU attendu : (5 × 5000) / 5 = 5000
        $arpu = $this->strategicService->calculateARPU();
        $this->assertEquals(5000, $arpu);
    }

    /**
     * Test : Stripe Health Score calculé correctement
     */
    public function test_stripe_health_score_calculated_correctly(): void
    {
        // Créer 10 comptes Stripe
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create(['role' => 'createur']);
            $creator = CreatorProfile::create([
                'user_id' => $user->id,
                'brand_name' => "Creator {$i}",
                'slug' => "creator-{$i}",
                'is_active' => true,
                'status' => 'active',
            ]);

            // 8 comptes complets, 2 incomplets
            $isComplete = $i <= 8;
            
            CreatorStripeAccount::create([
                'creator_profile_id' => $creator->id,
                'stripe_account_id' => "acct_test_{$i}",
                'account_type' => 'express',
                'charges_enabled' => $isComplete,
                'payouts_enabled' => $isComplete,
                'onboarding_status' => $isComplete ? 'complete' : 'in_progress',
                'details_submitted' => $isComplete,
            ]);
        }

        $healthScore = $this->strategicService->calculateStripeHealthScore();

        // Score attendu : (80% + 80% + 80%) / 3 = 80%
        $this->assertEquals(80, $healthScore['score']);
        $this->assertEquals(80, $healthScore['charges_enabled_rate']);
        $this->assertEquals(80, $healthScore['payouts_enabled_rate']);
        $this->assertEquals(80, $healthScore['onboarding_complete_rate']);
    }
}

