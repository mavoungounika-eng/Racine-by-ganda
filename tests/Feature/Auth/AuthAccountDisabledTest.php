<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Tests de désactivation de compte pendant une session active
 * 
 * Vérifie que les comptes désactivés perdent immédiatement l'accès
 * et que les sessions sont correctement nettoyées
 */
class AuthAccountDisabledTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un compte désactivé pendant une session perd l'accès immédiatement
     */
    public function test_account_disabled_during_session_loses_access(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'role' => 'client',
        ]);

        $this->actingAs($user);

        // Vérifier l'accès initial
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(200);

        // Désactiver le compte
        $user->update(['active' => false]);

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
            'active' => true,
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // Accès initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Désactiver le compte
        $admin->update(['active' => false]);

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
            'active' => true,
            'role' => 'creator',
        ]);

        $creator->creatorProfile()->create([
            'status' => 'active',
            'shop_name' => 'Test Shop',
        ]);

        $this->actingAs($creator);

        // Accès initial
        $response = $this->get(route('creator.dashboard'));
        $response->assertStatus(200);

        // Désactiver le compte
        $creator->update(['active' => false]);

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
            'active' => true,
            'role' => 'client',
        ]);

        $this->actingAs($user);

        // Vérifier que l'utilisateur est authentifié
        $this->assertTrue(auth()->check());

        // Désactiver le compte
        $user->update(['active' => false]);

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
            'active' => false,
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
            'active' => false,
            'role' => 'client',
        ]);

        // Réactiver le compte
        $user->update(['active' => true]);

        // Se connecter
        $this->actingAs($user);

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
            'active' => true,
            'role' => 'client',
        ]);

        // Connecter le premier utilisateur
        $this->actingAs($users[0]);

        // Vérifier l'accès initial
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(200);

        // Désactiver tous les comptes en batch
        User::whereIn('id', $users->pluck('id'))->update(['active' => false]);

        // Vérifier que l'accès est bloqué
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(403);

        // Vérifier que tous les comptes sont désactivés
        foreach ($users as $user) {
            $this->assertFalse($user->fresh()->active);
        }
    }

    /**
     * Test qu'un compte désactivé ne peut pas effectuer d'actions sensibles
     */
    public function test_disabled_account_cannot_perform_sensitive_actions(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'role' => 'client',
        ]);

        $this->actingAs($user);

        // Désactiver le compte
        $user->update(['active' => false]);

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
            'active' => true,
            'role' => 'client',
        ]);

        $this->actingAs($user);

        // Vérifier que la session existe
        $this->assertTrue(Session::has('_token'));

        // Désactiver le compte
        $user->update(['active' => false]);

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
            'active' => true,
            'role' => 'client',
        ]);

        // Créer une commande en attente
        $order = \App\Models\Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        // Désactiver le compte
        $user->update(['active' => false]);

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
