<?php

namespace Tests\Unit;

use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Services\Analytics\BiMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests Unit - BI Metrics Global
 * 
 * PRIORITÉ 5 - Analytics & BI (READ-ONLY)
 * 
 * Scénarios OBLIGATOIRES :
 * - Cohérence financière
 * - READ-ONLY
 * - Cas limites
 */
class BiMetricsGlobalTest extends TestCase
{
    use RefreshDatabase;

    protected BiMetricsService $biService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->biService = app(BiMetricsService::class);
    }

    /**
     * Test : Cohérence financière - ARR = MRR × 12
     */
    public function test_arr_equals_mrr_times_twelve(): void
    {
        // Créer des abonnements de test
        $plan = CreatorPlan::factory()->create([
            'price' => 10000,
            'code' => 'premium',
        ]);
        
        $creator = CreatorProfile::factory()->create();
        
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now()->subMonth(),
        ]);
        
        // Calculer MRR et ARR
        $mrr = $this->biService->calculateMRR();
        $arr = $this->biService->calculateARR();
        
        // Vérifier que ARR = MRR × 12
        $expectedArr = round($mrr * 12, 2);
        $this->assertEquals($expectedArr, $arr, "ARR devrait être égal à MRR × 12. MRR: {$mrr}, ARR: {$arr}, Attendu: {$expectedArr}");
    }

    /**
     * Test : Cohérence financière - ARPU cohérent
     */
    public function test_arpu_is_consistent(): void
    {
        // Créer des abonnements de test
        $plan = CreatorPlan::factory()->create([
            'price' => 10000,
            'code' => 'premium',
        ]);
        
        $creator1 = CreatorProfile::factory()->create();
        $creator2 = CreatorProfile::factory()->create();
        
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator1->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now()->subMonth(),
        ]);
        
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator2->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now()->subMonth(),
        ]);
        
        // Calculer MRR et ARPU
        $mrr = $this->biService->calculateMRR();
        $arpu = $this->biService->calculateARPU();
        
        // ARPU devrait être MRR / nombre de créateurs payants
        // Avec 2 créateurs payants à 10000 XAF/mois chacun, MRR = 20000, ARPU = 10000
        $this->assertGreaterThan(0, $arpu, "ARPU devrait être > 0");
        $this->assertLessThanOrEqual($mrr, $arpu * 2, "ARPU devrait être cohérent avec MRR");
    }

    /**
     * Test : Cohérence financière - Churn jamais négatif
     */
    public function test_churn_never_negative(): void
    {
        // Calculer le churn
        $churnMonth = $this->biService->calculateChurnRate('month');
        $churnYear = $this->biService->calculateChurnRate('year');
        
        // Vérifier que le churn n'est jamais négatif
        $this->assertGreaterThanOrEqual(0, $churnMonth, "Churn mensuel ne devrait jamais être négatif");
        $this->assertGreaterThanOrEqual(0, $churnYear, "Churn annuel ne devrait jamais être négatif");
    }

    /**
     * Test : READ-ONLY - Aucune écriture DB
     */
    public function test_bi_metrics_no_db_writes(): void
    {
        // Compter les écritures DB avant
        DB::enableQueryLog();
        
        // Appeler toutes les méthodes BI
        $this->biService->calculateMRR();
        $this->biService->calculateARR();
        $this->biService->calculateARPU();
        $this->biService->calculateChurnRate('month');
        $this->biService->calculateLTV();
        
        $queries = DB::getQueryLog();
        
        // Vérifier qu'aucune requête n'est une écriture (INSERT, UPDATE, DELETE)
        foreach ($queries as $query) {
            $sql = strtoupper($query['query']);
            $this->assertStringNotContainsString('INSERT', $sql, "BI Metrics ne devrait pas faire d'INSERT");
            $this->assertStringNotContainsString('UPDATE', $sql, "BI Metrics ne devrait pas faire d'UPDATE");
            $this->assertStringNotContainsString('DELETE', $sql, "BI Metrics ne devrait pas faire de DELETE");
        }
    }

    /**
     * Test : READ-ONLY - Aucun observer déclenché
     */
    public function test_bi_metrics_no_observers_triggered(): void
    {
        // Créer un abonnement
        $plan = CreatorPlan::factory()->create(['price' => 10000]);
        $creator = CreatorProfile::factory()->create();
        
        $subscription = CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
        ]);
        
        // Compter les abonnements avant
        $countBefore = CreatorSubscription::count();
        
        // Appeler les méthodes BI
        $this->biService->calculateMRR();
        $this->biService->calculateARR();
        $this->biService->calculateARPU();
        
        // Vérifier qu'aucun nouvel enregistrement n'a été créé
        $countAfter = CreatorSubscription::count();
        $this->assertEquals($countBefore, $countAfter, "BI Metrics ne devrait pas créer d'enregistrements");
    }

    /**
     * Test : Cas limites - 0 abonnements
     */
    public function test_bi_metrics_with_zero_subscriptions(): void
    {
        // Ne créer aucun abonnement
        
        // Calculer les métriques
        $mrr = $this->biService->calculateMRR();
        $arr = $this->biService->calculateARR();
        $arpu = $this->biService->calculateARPU();
        
        // Vérifier que les valeurs sont 0 ou cohérentes
        $this->assertEquals(0, $mrr, "MRR devrait être 0 sans abonnements");
        $this->assertEquals(0, $arr, "ARR devrait être 0 sans abonnements");
        $this->assertEquals(0, $arpu, "ARPU devrait être 0 sans abonnements");
    }

    /**
     * Test : Cas limites - 0 créateurs payants
     */
    public function test_bi_metrics_with_zero_paying_creators(): void
    {
        // Créer un plan gratuit
        $freePlan = CreatorPlan::factory()->create([
            'price' => 0,
            'code' => 'free',
        ]);
        
        $creator = CreatorProfile::factory()->create();
        
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $freePlan->id,
            'status' => 'active',
        ]);
        
        // Calculer ARPU
        $arpu = $this->biService->calculateARPU();
        
        // ARPU devrait être 0 si aucun créateur payant
        $this->assertEquals(0, $arpu, "ARPU devrait être 0 sans créateurs payants");
    }

    /**
     * Test : Cas limites - Abonnements expirés exclus
     */
    public function test_expired_subscriptions_excluded(): void
    {
        // Créer un plan payant
        $plan = CreatorPlan::factory()->create([
            'price' => 10000,
            'code' => 'premium',
        ]);
        
        $creator = CreatorProfile::factory()->create();
        
        // Créer un abonnement expiré
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $creator->id,
            'creator_plan_id' => $plan->id,
            'status' => 'canceled',
            'ends_at' => now()->subDay(), // Expiré hier
        ]);
        
        // Calculer MRR
        $mrr = $this->biService->calculateMRR();
        
        // MRR devrait être 0 car l'abonnement est expiré
        $this->assertEquals(0, $mrr, "MRR devrait être 0 avec uniquement des abonnements expirés");
    }
}



