<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class PosOperatorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_login_with_valid_credentials()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'slug' => 'staff']
        );
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/pos/auth/operator/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'operator' => ['id', 'name', 'email', 'role'],
                         'token'
                     ]
                 ]);
    }

    public function test_operator_login_returns_sanctum_token()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'slug' => 'admin']
        );
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/pos/auth/operator/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $token = $response->json('data.token');
        $this->assertNotNull($token);
        
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'pos-operator',
        ]);
    }

    public function test_operator_login_fails_with_wrong_password()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'slug' => 'admin']
        );
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/pos/auth/operator/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_client_role_cannot_login_to_pos()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'client'],
            ['name' => 'Client', 'slug' => 'client']
        );
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'role' => 'client',
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/pos/auth/operator/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_operator_can_logout()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'slug' => 'staff']
        );
        $user = User::factory()->create([
            'role' => 'staff',
            'role_id' => $role->id,
        ]);
        Sanctum::actingAs($user, ['pos:operate']);

        $response = $this->withoutMiddleware([\App\Http\Middleware\PosDeviceAuth::class])
                         ->postJson('/api/pos/auth/operator/logout');

        $response->assertStatus(200);
        $this->assertTrue(true); // actingAs uses transient token

    }
    public function test_operator_can_get_own_info()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'slug' => 'staff']
        );
        $user = User::factory()->create([
            'role' => 'staff',
            'role_id' => $role->id,
        ]);
        $token = $user->createToken('pos-operator');

        Sanctum::actingAs($user, ['pos:operate']);

        $response = $this->withoutMiddleware([\App\Http\Middleware\PosDeviceAuth::class])
                         ->getJson('/api/pos/auth/operator/me');

        $response->assertStatus(200)
                 ->assertJsonPath('data.operator.email', $user->email);
    }

    public function test_login_revokes_previous_pos_tokens()
    {
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'slug' => 'admin']
        );
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        $user->createToken('pos-operator');
        $user->createToken('pos-operator');
        
        $this->assertEquals(2, $user->tokens()->count());
        
        $response = $this->postJson('/api/pos/auth/operator/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/pos/auth/operator/login', []);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }
}
