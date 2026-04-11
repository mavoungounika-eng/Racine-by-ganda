<?php

namespace Tests\Feature\Governance;

use App\Models\CreatorMember;
use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class MultiAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    /** @test */
    public function a_user_can_be_member_of_multiple_creator_profiles()
    {
        $user = User::factory()->create();
        $user->update(['role' => Role::CREATEUR]);

        $profileA = CreatorProfile::factory()->create(['brand_name' => 'Brand A']);
        $profileB = CreatorProfile::factory()->create(['brand_name' => 'Brand B']);

        CreatorMember::create([
            'user_id' => $user->id,
            'creator_profile_id' => $profileA->id,
            'role' => 'owner'
        ]);

        CreatorMember::create([
            'user_id' => $user->id,
            'creator_profile_id' => $profileB->id,
            'role' => 'editor'
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson(route('api.auth.accounts.list'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'accounts')
            ->assertJsonFragment(['brand_name' => 'Brand A'])
            ->assertJsonFragment(['brand_name' => 'Brand B']);
    }

    /** @test */
    public function switching_account_updates_user_context_in_session()
    {
        $user = User::factory()->create();
        $user->update(['role' => Role::CREATEUR]);

        $profileA = CreatorProfile::factory()->create();
        $profileB = CreatorProfile::factory()->create();

        CreatorMember::create(['user_id' => $user->id, 'creator_profile_id' => $profileA->id, 'role' => 'owner']);
        CreatorMember::create(['user_id' => $user->id, 'creator_profile_id' => $profileB->id, 'role' => 'viewer']);

        $this->actingAs($user, 'sanctum');

        // Switch to Profile B
        $response = $this->postJson(route('api.auth.accounts.switch', ['creatorId' => $profileB->id]));

        $response->assertStatus(200)
            ->assertJson(['active_creator_id' => $profileB->id, 'role' => 'viewer']);

        $this->assertEquals($profileB->id, session('active_creator_id'));
        
        $context = session('user_context');
        $this->assertEquals($profileB->id, $context['active_creator_id']);
        $this->assertEquals('viewer', $context['creator_role']);
    }

    /** @test */
    public function a_user_cannot_switch_to_an_account_they_do_not_belong_to()
    {
        $user = User::factory()->create();
        $user->update(['role' => Role::CREATEUR]);

        $otherProfile = CreatorProfile::factory()->create();

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson(route('api.auth.accounts.switch', ['creatorId' => $otherProfile->id]));

        $response->assertStatus(403);
    }
}
