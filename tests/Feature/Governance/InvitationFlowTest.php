<?php

namespace Tests\Feature\Governance;

use App\Models\CreatorInvitation;
use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    /** @test */
    public function an_owner_can_invite_a_new_member()
    {
        $owner = User::factory()->create();
        $owner->update(['role' => Role::CREATEUR]);
        $profile = CreatorProfile::factory()->create();
        
        // Simuler le membership owner
        $owner->creatorProfiles()->attach($profile->id, ['role' => 'owner']);
        
        $this->actingAs($owner, 'sanctum');
        session(['active_creator_id' => $profile->id]);

        $response = $this->postJson(route('api.creator.team.invite'), [
            'email' => 'new-member@example.com',
            'role' => 'editor'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('creator_invitations', [
            'creator_profile_id' => $profile->id,
            'email' => 'new-member@example.com',
            'role' => 'editor',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function a_user_can_accept_an_invitation()
    {
        $profile = CreatorProfile::factory()->create();
        $invitation = CreatorInvitation::create([
            'creator_profile_id' => $profile->id,
            'email' => 'invitee@example.com',
            'role' => 'admin',
            'invited_by' => User::factory()->create()->id,
            'status' => 'pending',
        ]);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invitee->update(['role' => Role::CREATEUR]);

        $this->actingAs($invitee, 'sanctum');

        // On va tricher un peu et appeler directement le service ou créer une route
        // Pour ce test, on vérifie la logique du CreatorTeamService
        $service = app(\App\Services\Creator\CreatorTeamService::class);
        $success = $service->acceptInvitation($invitation->token, $invitee);

        $this->assertTrue($success);
        $this->assertDatabaseHas('creator_members', [
            'user_id' => $invitee->id,
            'creator_profile_id' => $profile->id,
            'role' => 'admin'
        ]);
        
        $invitation->refresh();
        $this->assertEquals('accepted', $invitation->status);
    }
}
