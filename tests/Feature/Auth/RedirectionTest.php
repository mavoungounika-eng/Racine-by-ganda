<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\CreatorProfile;
use App\Services\Auth\PostLoginDecisionEngine;
use App\Services\Auth\UserContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles()
    {
        Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Createur', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    /**
     * Test : Redirection Admin.
     */
    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role_id' => Role::where('slug', 'admin')->first()->id]);
        
        $engine = new PostLoginDecisionEngine();
        $resolver = new UserContextResolver();
        $context = $resolver->resolve($admin);
        
        $url = $engine->determineRedirect($context);
        
        $this->assertEquals(route('admin.dashboard'), $url);
    }

    /**
     * Test : Redirection Créateur Actif vs Pending.
     */
    public function test_creator_redirection_depends_on_status(): void
    {
        $role = Role::where('slug', 'createur')->first();
        $engine = new PostLoginDecisionEngine();
        $resolver = new UserContextResolver();

        // Cas 1 : Créateur Actif
        $activeCreator = User::factory()->create(['role_id' => $role->id]);
        CreatorProfile::factory()->create(['user_id' => $activeCreator->id, 'status' => 'active']);
        $urlActive = $engine->determineRedirect($resolver->resolve($activeCreator));
        $this->assertEquals(route('creator.dashboard'), $urlActive);

        // Cas 2 : Créateur Pending
        $pendingCreator = User::factory()->create(['role_id' => $role->id]);
        CreatorProfile::factory()->create(['user_id' => $pendingCreator->id, 'status' => 'pending']);
        $urlPending = $engine->determineRedirect($resolver->resolve($pendingCreator));
        $this->assertEquals(route('creator.pending'), $urlPending);
    }
}
