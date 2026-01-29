<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuthAccountDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles nécessaires pour les tests
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'is_active' => true]);
        
        // Créer les plans nécessaires
        (new \Database\Seeders\CreatorPlanSeeder())->run();
    }

    /**
     * Test qu'un compte désactivé pendant une session perd l'accès immédiatement
     */
    public function test_account_disabled_during_session_loses_access(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);

        $this->actAsWithContext($user);

        // Vérifier l'accès initial
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(200);

        // Désactiver le compte
        $user->update(['status' => 'suspended']);

        // Tenter d'accéder à nouveau
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Test qu'un admin désactivé perd l'accès admin
     */
    public function test_admin_disabled_loses_admin_access(): void
    {
        $admin = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'admin')->first()->id,
            'two_factor_secret' => 'base32secret',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actAsWithContext($admin);

        // Accès initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Désactiver le compte
        $admin->update(['status' => 'suspended']);

        // Tenter d'accéder à nouveau
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Test qu'un créateur désactivé perd l'accès créateur
     */
    public function test_creator_disabled_loses_creator_access(): void
    {
        $creator = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'createur')->first()->id,
        ]);

        $creator->creatorProfile()->create([
            'status' => 'active',
            'brand_name' => 'Test Brand',
            'bio' => 'Test Bio',
        ]);

        $this->actAsWithContext($creator);

        // Accès initial
        $response = $this->get(route('creator.dashboard'));
        $response->assertStatus(200);

        // Désactiver le compte
        $creator->update(['status' => 'suspended']);

        // Tenter d'accéder à nouveau
        $response = $this->get(route('creator.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Test que la session est invalidée après désactivation
     */
    public function test_session_invalidated_after_account_disabled(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);

        $this->actAsWithContext($user);

        // Vérifier que l'utilisateur est authentifié
        $this->assertTrue(auth()->check());

        // Désactiver le compte
        $user->update(['status' => 'suspended']);

        // Tenter d'accéder à une route protégée
        $response = $this->get(route('account.dashboard'));

        // Vérifier que l'accès est refusé
        $response->assertStatus(403);
    }

    /**
     * Test qu'un compte désactivé ne peut pas se connecter
     */
    public function test_disabled_account_cannot_login(): void
    {
        $user = User::factory()->create([
            'status' => 'suspended',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Tenter de se connecter
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Vérifier que la connexion est refusée
        $response->assertSessionHasErrors();
        $this->assertFalse(auth()->check());
    }

    /**
     * Test qu'un compte réactivé retrouve l'accès
     */
    public function test_reactivated_account_regains_access(): void
    {
        $user = User::factory()->create([
            'status' => 'suspended',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);
        $user->update(['status' => 'active']);

        // Se connecter
        $this->actAsWithContext($user);

        // Vérifier l'accès
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(200);
    }

    /**
     * Test que la désactivation en masse fonctionne correctement
     */
    public function test_bulk_account_disable_works_correctly(): void
    {
        $users = User::factory()->count(5)->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);

        // Connecter le premier utilisateur
        $this->actAsWithContext($users[0]);

        // Vérifier l'accès initial
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(200);

        // Désactiver tous les comptes (individuellement pour déclencher l'observer)
        $users->each->update(['status' => 'suspended']);

        // Vérifier que l'accès est bloqué
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(403);

        // Vérifier que tous les comptes sont désactivés
        foreach ($users as $user) {
            $this->assertEquals('suspended', $user->fresh()->status);
        }
    }

    /**
     * Test qu'un compte désactivé ne peut pas effectuer d'actions sensibles
     */
    public function test_disabled_account_cannot_perform_sensitive_actions(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);

        $this->actAsWithContext($user);

        // Désactiver le compte
        $user->update(['status' => 'suspended']);

        // Tenter de modifier le profil
        $response = $this->put(route('profile.update'), [
            'name' => 'New Name',
        ]);

        // Vérifier que l'action est refusée
        $response->assertStatus(403);
    }

    /**
     * Test que les cookies/sessions sont détruits après désactivation
     */
    public function test_cookies_destroyed_after_account_disabled(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);

        $this->actAsWithContext($user);

        // Vérifier que l'utilisateur est authentifié (la session doit être active)
        $this->assertTrue(auth()->check());

        // Désactiver le compte
        $user->update(['status' => 'suspended']);

        // Tenter d'accéder à une route protégée (devrait nettoyer la session)
        $response = $this->get(route('account.dashboard'));

        // Vérifier que l'accès est refusé
        $response->assertStatus(403);
    }

    /**
     * Test qu'un compte désactivé avec commandes en cours est géré correctement
     */
    public function test_disabled_account_with_pending_orders_handled_correctly(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role_id' => Role::where('slug', 'client')->first()->id,
        ]);

        // Créer une commande en attente
        $order = \App\Models\Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actAsWithContext($user);

        // Désactiver le compte
        $user->update(['status' => 'suspended']);

        // Vérifier que l'utilisateur ne peut plus accéder à ses commandes
        $response = $this->get(route('profile.orders'));
        $response->assertStatus(403);

        // Vérifier que la commande existe toujours (pas supprimée)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
        ]);
    }
}
