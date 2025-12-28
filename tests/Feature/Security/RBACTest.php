<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_access_admin_dashboard()
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_creator_cannot_access_admin_dashboard()
    {
        $creator = User::factory()->create(['role' => 'creator']);

        $this->actingAs($creator)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertStatus(200);
    }
}
