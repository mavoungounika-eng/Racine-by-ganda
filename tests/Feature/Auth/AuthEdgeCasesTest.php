<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests des cas limites et edge cases sécurité auth
 * 
 * Objectif : Garantir la robustesse du système auth face aux cas limites
 * et situations exceptionnelles
 */
class AuthEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed comptes test
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\TestUsersSeeder::class);
        $this->seed(\Database\Seeders\CreatorPlanSeeder::class);
        $this->seed(\Database\Seeders\PlanCapabilitySeeder::class);
    }

    /**
     * Test : Session expirée redirige vers login
     */
    public function test_expired_session_redirects_to_login(): void
    {
        $user = User::where('email', 'client@racine.cm')->first();

        // Simuler session expirée en accédant sans session valide
        $response = $this->get('/compte');

        $response->assertRedirect('/login');
    }

    /**
     * Test : Credentials invalides refusent login
     */
    public function test_invalid_credentials_deny_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'client@racine.cm',
            'password' => 'wrong_password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }
}
