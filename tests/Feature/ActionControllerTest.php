<?php

namespace Tests\Feature;

use App\Models\AdminActionDecision;
use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature - ActionController
 * 
 * Phase 8.4 - Tests d'intégration de l'interface admin
 */
class ActionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create();
    }

    /** @test */
    public function it_returns_pending_actions()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'pending',
            'justification' => 'Test action',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/actions/pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'actions',
                'total_count',
            ]);
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

        $response = $this->actingAs($this->adminUser)
            ->postJson("/admin/actions/creator/{$creator->id}/propose");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'proposals',
                'created_actions',
                'message',
            ]);
    }

    /** @test */
    public function it_approves_action()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'pending',
            'justification' => 'Test action',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/admin/actions/{$actionDecision->id}/approve", [
                'decision_reason' => 'Action approved for testing',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Action approved',
            ]);

        $actionDecision->refresh();
        $this->assertEquals('approved', $actionDecision->status);
        $this->assertEquals($this->adminUser->id, $actionDecision->approved_by);
    }

    /** @test */
    public function it_rejects_action()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'pending',
            'justification' => 'Test action',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/admin/actions/{$actionDecision->id}/reject", [
                'decision_reason' => 'Action not needed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Action rejected',
            ]);

        $actionDecision->refresh();
        $this->assertEquals('rejected', $actionDecision->status);
        $this->assertEquals($this->adminUser->id, $actionDecision->rejected_by);
    }

    /** @test */
    public function it_executes_approved_action()
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

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'approved',
            'justification' => 'Test action',
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/admin/actions/{$actionDecision->id}/execute");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Action executed successfully',
            ]);

        $actionDecision->refresh();
        $this->assertEquals('executed', $actionDecision->status);
    }

    /** @test */
    public function it_blocks_execution_of_non_approved_action()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'pending', // Pas approuvé
            'justification' => 'Test action',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/admin/actions/{$actionDecision->id}/execute");

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Action cannot be executed. Status: pending',
            ]);
    }

    /** @test */
    public function it_requires_confirmation_for_critical_actions()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'PROPOSE_SUSPENSION',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'approved',
            'justification' => 'Test suspension',
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        // Sans confirmation
        $response = $this->actingAs($this->adminUser)
            ->postJson("/admin/actions/{$actionDecision->id}/execute");

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Critical action requires explicit confirmation',
                'requires_confirmation' => true,
            ]);
    }

    /** @test */
    public function it_returns_action_history()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'executed',
            'justification' => 'Test action',
            'executed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/admin/actions/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'history',
                'total_count',
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/admin/actions/pending');

        $response->assertStatus(401);
    }
}



