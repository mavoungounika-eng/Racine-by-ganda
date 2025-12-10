<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_is_redirected_based_on_role_after_login()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function client_is_redirected_to_account_dashboard()
    {
        $client = User::factory()->create([
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $client->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
    }

    /** @test */
    public function creator_is_redirected_to_creator_dashboard()
    {
        $creator = User::factory()->create([
            'role' => 'createur',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $creator->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('creator.dashboard'));
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('frontend.home'));
        $this->assertGuest();
    }

    /** @test */
    public function login_has_rate_limiting()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Tenter plusieurs connexions échouées
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login.post'), [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        // La 6ème tentative devrait être bloquée
        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }
}

