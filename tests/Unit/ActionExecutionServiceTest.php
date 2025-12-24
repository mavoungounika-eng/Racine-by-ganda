<?php

namespace Tests\Unit;

use App\Models\AdminActionDecision;
use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Action\ActionExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitaires - ActionExecutionService
 * 
 * Phase 8.3 - Tests d'exécution d'actions
 */
class ActionExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActionExecutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActionExecutionService();
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

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'approved',
            'justification' => 'Test action',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $result = $this->service->execute($actionDecision);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('result', $result);
        
        $actionDecision->refresh();
        $this->assertEquals('executed', $actionDecision->status);
        $this->assertNotNull($actionDecision->executed_at);
    }

    /** @test */
    public function it_blocks_execution_of_non_approved_action()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'pending', // Pas approuvé
            'justification' => 'Test action',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Action cannot be executed');

        $this->service->execute($actionDecision);
    }

    /** @test */
    public function it_captures_state_before_and_after()
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
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->service->execute($actionDecision);

        $actionDecision->refresh();
        $this->assertNotNull($actionDecision->state_before);
        $this->assertNotNull($actionDecision->state_after);
        $this->assertIsArray($actionDecision->state_before);
        $this->assertIsArray($actionDecision->state_after);
    }

    /** @test */
    public function it_handles_execution_failure()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Créer une action avec un type invalide pour forcer l'échec
        $actionDecision = AdminActionDecision::create([
            'action_type' => 'INVALID_ACTION',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'approved',
            'justification' => 'Test action',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $result = $this->service->execute($actionDecision);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        $actionDecision->refresh();
        $this->assertEquals('failed', $actionDecision->status);
    }

    /** @test */
    public function it_executes_monitor_action()
    {
        $plan = CreatorPlan::factory()->create(['price' => 5000]);
        $user = User::factory()->create();
        $creator = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        $actionDecision = AdminActionDecision::create([
            'action_type' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'status' => 'approved',
            'justification' => 'Test monitor',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $result = $this->service->execute($actionDecision);

        $this->assertTrue($result['success']);
        $this->assertEquals('logged', $result['result']['action']);
    }
}



