<?php

namespace Tests\Feature;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature - DecisionIntelligenceController
 * 
 * Phase 7.5 - Tests d'intégration de l'interface admin
 */
class DecisionIntelligenceControllerTest extends TestCase
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
    public function it_returns_decision_analysis_for_creator()
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

        $response = $this->actingAs($this->adminUser)
            ->getJson("/admin/decision/creator/{$creator->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'creator_id',
                'creator_name',
                'snapshot_date',
                'decision_score' => [
                    'global_score',
                    'qualitative_grade',
                    'components',
                    'strengths',
                    'weaknesses',
                    'confidence_level',
                ],
                'churn_prediction' => [
                    'churn_probability',
                    'risk_score',
                    'classification',
                    'factors',
                ],
                'recommendations' => [
                    'recommendations',
                    'total_count',
                ],
                'risk_assessment' => [
                    'risk_level',
                    'risk_score',
                    'reasons',
                ],
                'alerts',
                'metadata',
            ]);
    }

    /** @test */
    public function it_returns_overview_of_creators()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // Créer plusieurs créateurs
        for ($i = 0; $i < 5; $i++) {
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
        }

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/decision/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overview' => [
                    '*' => [
                        'creator_id',
                        'creator_name',
                        'decision_score',
                        'qualitative_grade',
                        'churn_probability',
                        'churn_classification',
                        'risk_level',
                        'risk_score',
                    ],
                ],
                'total_creators',
                'generated_at',
            ]);
    }

    /** @test */
    public function it_handles_nonexistent_creator()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/decision/creator/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_filters_overview_by_score()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);

        // Créer des créateurs avec différents profils
        for ($i = 0; $i < 3; $i++) {
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
        }

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/decision/overview?min_score=50&max_score=100');

        $response->assertStatus(200);
        $data = $response->json();
        
        foreach ($data['overview'] as $item) {
            $this->assertGreaterThanOrEqual(50, $item['decision_score']);
            $this->assertLessThanOrEqual(100, $item['decision_score']);
        }
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/admin/decision/overview');

        $response->assertStatus(401);
    }
}



