<?php

namespace Tests\Feature\Auth;

use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests Feature - Login Client & Créateur (Formulaire Email/Password)
 * 
 * Phase B1 - Tests de connexion formulaire
 * Garantit que tous les moyens d'authentification fonctionnent
 */
class LoginClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles nécessaires
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'is_active' => true]);
    }

    /**
     * B1.1 - Connexion client classique
     * 
     * Vérifie :
     * - Auth OK
     * - Redirection /compte
     * - Session valide
     */
    #[Test]
    public function client_can_login_with_email_and_password(): void
    {
        $role = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * B1.2 - Connexion créateur actif
     * 
     * Vérifie que le créateur actif est redirigé vers le dashboard créateur
     */
    #[Test]
    public function creator_active_redirects_to_creator_dashboard(): void
    {
        $role = Role::where('slug', 'createur')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);
        
        CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('creator.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * B1.3 - Créateur pending
     * 
     * Vérifie que le créateur en attente est redirigé vers la page pending
     */
    #[Test]
    public function creator_pending_redirects_to_pending_page(): void
    {
        $role = Role::where('slug', 'createur')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);
        
        CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('creator.pending'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * B1.4 - Créateur suspendu
     * 
     * Vérifie que le créateur suspendu est redirigé vers la page suspended
     */
    #[Test]
    public function creator_suspended_redirects_to_suspended_page(): void
    {
        $role = Role::where('slug', 'createur')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);
        
        CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'suspended',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('creator.suspended'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * B1.5 - Utilisateur déjà connecté redirigé
     * 
     * Vérifie qu'un utilisateur déjà connecté est redirigé selon son rôle
     */
    #[Test]
    public function authenticated_client_is_redirected_when_accessing_login(): void
    {
        $role = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('login'));
        $response->assertRedirect(route('account.dashboard'));
    }

    /**
     * B1.6 - Échec de connexion avec mauvais identifiants
     * 
     * Vérifie que les mauvais identifiants sont rejetés
     */
    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        $role = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}



