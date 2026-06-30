<?php

namespace Tests\Unit;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CanAccessPosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function staff_member_can_access_pos()
    {
        $staff = User::factory()->create(['role' => 'staff', 'role_id' => null]);

        $this->assertTrue($staff->canAccessPos());
    }

    #[Test]
    public function super_admin_can_access_pos()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'role_id' => null]);

        $this->assertTrue($superAdmin->canAccessPos());
    }

    #[Test]
    public function creator_with_active_signature_subscription_can_access_pos()
    {
        $plan = CreatorPlan::factory()->create(['code' => 'signature', 'has_pos' => true]);
        $user = User::factory()->create(['role' => 'createur', 'role_id' => null]);
        $profile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $profile->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => null,
        ]);

        $this->assertTrue($user->canAccessPos());
    }

    #[Test]
    public function creator_with_active_atelier_subscription_cannot_access_pos()
    {
        $plan = CreatorPlan::factory()->create(['code' => 'atelier', 'has_pos' => false]);
        $user = User::factory()->create(['role' => 'createur', 'role_id' => null]);
        $profile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $profile->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => null,
        ]);

        $this->assertFalse($user->canAccessPos());
    }

    #[Test]
    public function creator_without_subscription_cannot_access_pos()
    {
        $user = User::factory()->create(['role' => 'createur', 'role_id' => null]);
        CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->assertFalse($user->canAccessPos());
    }

    #[Test]
    public function creator_with_expired_signature_subscription_cannot_access_pos()
    {
        $plan = CreatorPlan::factory()->create(['code' => 'signature', 'has_pos' => true]);
        $user = User::factory()->create(['role' => 'createur', 'role_id' => null]);
        $profile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'active',
        ]);
        CreatorSubscription::factory()->create([
            'creator_profile_id' => $profile->id,
            'creator_id' => $user->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($user->canAccessPos());
    }

    #[Test]
    public function client_cannot_access_pos()
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->assertFalse($client->canAccessPos());
    }
}
