<?php

namespace Tests\Feature;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\CreatorSubscriptionInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature - Dashboard Financier Admin
 * 
 * Phase 6.1 - Tests d'intégration du dashboard financier
 */
class AdminFinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin
        $this->adminUser = User::factory()->create();
        // TODO: Ajouter le rôle admin si nécessaire
    }

    /** @test */
    public function it_returns_dashboard_metrics_for_admin()
    {
        // Créer des données de test
        $this->createTestData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/financial/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
                'revenue' => [
                    'mrr',
                    'arr',
                    'total_revenue',
                    'current_month_revenue',
                    'previous_month_revenue',
                    'mom_variation_percent',
                ],
                'subscriptions' => [
                    'active',
                    'trialing',
                    'past_due',
                    'unpaid',
                    'canceled',
                    'total',
                ],
                'creators' => [
                    'total',
                    'active',
                    'blocked',
                    'onboarding_incomplete',
                    'eligible_for_payments',
                ],
                'stripe_health' => [
                    'charges_enabled_percent',
                    'payouts_enabled_percent',
                    'onboarding_complete_percent',
                    'failed_accounts',
                    'total_accounts',
                ],
                'risks' => [
                    'creators_past_due',
                    'creators_unpaid',
                    'failed_payments_7_days',
                    'high_risk_creators',
                ],
                'advanced_kpis' => [
                    'churn_rate_month',
                    'churn_rate_year',
                    'ltv',
                    'arpu',
                    'average_subscription_duration',
                ],
                'alerts',
            ]);
    }

    /** @test */
    public function it_handles_empty_database()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/financial/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'revenue' => [
                    'mrr' => 0,
                    'arr' => 0,
                    'total_revenue' => 0,
                ],
                'subscriptions' => [
                    'active' => 0,
                    'total' => 0,
                ],
                'creators' => [
                    'total' => 0,
                    'active' => 0,
                ],
            ]);
    }

    /** @test */
    public function it_calculates_mrr_correctly()
    {
        // Créer un plan OFFICIEL à 5000 XAF
        $plan = CreatorPlan::factory()->create([
            'code' => 'official',
            'price' => 5000,
        ]);

        // Créer 3 abonnements actifs
        for ($i = 0; $i < 3; $i++) {
            $creator = CreatorProfile::factory()->create();
            CreatorSubscription::factory()->create([
                'creator_profile_id' => $creator->id,
                'creator_plan_id' => $plan->id,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/financial/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'revenue' => [
                    'mrr' => 15000.0, // 3 × 5000
                ],
            ]);
    }

    /** @test */
    public function it_calculates_churn_rate_correctly()
    {
        // Créer des abonnements actifs et annulés
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // 10 abonnements actifs
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

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/financial/dashboard');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Churn = 2 / 10 = 20%
        $this->assertGreaterThanOrEqual(15, $data['advanced_kpis']['churn_rate_month']);
        $this->assertLessThanOrEqual(25, $data['advanced_kpis']['churn_rate_month']);
    }

    /** @test */
    public function it_returns_snapshot_for_bi_export()
    {
        $this->createTestData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/financial/snapshot?period=month');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'snapshot_date',
                'period',
                'revenue',
                'subscriptions',
                'creators',
                'stripe_health',
                'risks',
                'advanced_kpis',
                'alerts',
            ]);
    }

    /**
     * Créer des données de test
     */
    private function createTestData(): void
    {
        $plan = CreatorPlan::factory()->create([
            'code' => 'official',
            'price' => 5000,
        ]);

        // Créer 5 créateurs avec abonnements actifs
        for ($i = 0; $i < 5; $i++) {
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
                'started_at' => now()->subMonths(1),
            ]);
        }

        // Créer quelques factures payées
        $subscription = CreatorSubscription::first();
        CreatorSubscriptionInvoice::factory()->create([
            'creator_subscription_id' => $subscription->id,
            'status' => 'paid',
            'amount' => 5000,
            'paid_at' => now(),
        ]);
    }
}



