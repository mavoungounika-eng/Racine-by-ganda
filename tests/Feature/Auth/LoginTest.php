<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un utilisateur peut se connecter avec des identifiants valides
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Créer un rôle client
        $role = Role::factory()->create(['slug' => 'client', 'name' => 'Client']);
        
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPassword123!'),
            'role_id' => $role->id,
        ]);

        // Tenter de se connecter
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        // Vérifier que l'utilisateur est authentifié
        $this->assertAuthenticated();
        
        // Vérifier la redirection
        $response->assertRedirect();
    }

    /**
     * Test qu'un utilisateur ne peut pas se connecter avec un mot de passe invalide
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        $role = Role::factory()->create(['slug' => 'client']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPassword123!'),
            'role_id' => $role->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test qu'un compte est bloqué après 5 tentatives échouées
     */
    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $role = Role::factory()->create(['slug' => 'client']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPassword123!'),
            'role_id' => $role->id,
        ]);

        // 5 tentatives échouées
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // La 6ème tentative devrait être bloquée même avec le bon mot de passe
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        // Vérifier que l'utilisateur n'est pas connecté
        $this->assertGuest();
        
        // Vérifier que le message contient "bloqu"
        $response->assertSessionHasErrors();
        $errors = session('errors');
        if ($errors) {
            $emailError = $errors->first('email');
            $this->assertStringContainsString('bloqu', strtolower($emailError));
        }
    }

    /**
     * Test qu'un utilisateur peut se déconnecter
     */
    public function test_user_can_logout(): void
    {
        $role = Role::factory()->create(['slug' => 'client']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * Test que les tentatives échouées sont effacées après une connexion réussie
     */
    public function test_failed_attempts_are_cleared_after_successful_login(): void
    {
        $role = Role::factory()->create(['slug' => 'client']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPassword123!'),
            'role_id' => $role->id,
        ]);

        // 3 tentatives échouées
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // Connexion réussie
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $this->assertAuthenticated();

        // Se déconnecter
        $this->post('/logout');

        // Les tentatives devraient être effacées, donc pas de blocage
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $this->assertAuthenticated();
    }
}
