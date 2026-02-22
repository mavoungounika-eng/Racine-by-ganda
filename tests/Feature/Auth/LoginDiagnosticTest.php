<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles before each test
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }
    #[Test]
    public function it_can_authenticate_with_orchestrator()
    {
        // Create user with seeded role
        $role = Role::where('slug', 'client')->first();
        $this->assertNotNull($role, 'Client role should exist after seeding');
        
        $user = User::factory()->create([
            'email' => 'diagnostic@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->assertNotNull($user->role_id, 'User should have role_id');
        $this->assertEquals($role->id, $user->role_id);

        // Test direct Auth::attempt
        $result = \Illuminate\Support\Facades\Auth::attempt([
            'email' => 'diagnostic@example.com',
            'password' => 'password123',
        ]);

        $this->assertTrue($result, 'Auth::attempt should succeed');
        $this->assertAuthenticated();

        // Get user and check role
        $authUser = \Illuminate\Support\Facades\Auth::user();
        $this->assertNotNull($authUser);
        
        $authUser->load('roleRelation');
        $this->assertNotNull($authUser->roleRelation, 'User should have roleRelation');
        $this->assertEquals('client', $authUser->roleRelation->slug);
    }
}
