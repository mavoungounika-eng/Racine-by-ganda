<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests d'intégrité du cache RBAC
 * 
 * Vérifie que les changements de permissions/rôles invalident correctement
 * le cache et qu'aucune élévation de privilèges n'est possible via cache stale
 */
class RbacCacheIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le cache de permissions est invalidé après changement de rôle
     */
    public function test_permission_cache_invalidated_after_role_change(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
        ]);

        $this->actingAs($user);

        // Vérifier l'accès initial (client)
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);

        // Mettre en cache les permissions (simuler comportement app)
        Cache::put("user.{$user->id}.permissions", ['client'], 3600);

        // Changer le rôle vers admin
        $user->update(['role' => 'admin']);

        // Vérifier que le cache est invalidé et l'accès est accordé
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Vérifier que le cache a été mis à jour
        $cachedPermissions = Cache::get("user.{$user->id}.permissions");
        $this->assertNotEquals(['client'], $cachedPermissions);
    }

    /**
     * Test qu'une permission ajoutée invalide le cache immédiatement
     */
    public function test_added_permission_invalidates_cache_immediately(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user);

        // Accès initial refusé (pas de permission payments.view)
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(403);

        // Ajouter la permission
        $user->givePermissionTo('payments.view');

        // Vérifier que l'accès est accordé immédiatement
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(200);
    }

    /**
     * Test qu'une permission révoquée invalide le cache immédiatement
     */
    public function test_revoked_permission_invalidates_cache_immediately(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Donner une permission spécifique
        $user->givePermissionTo('orders.delete');

        $this->actingAs($user);

        // Accès initial accordé
        $response = $this->delete(route('admin.orders.destroy', ['order' => 1]));
        // Note: peut retourner 404 si order n'existe pas, mais pas 403

        // Révoquer la permission
        $user->revokePermissionTo('orders.delete');

        // Vérifier que l'accès est refusé immédiatement
        $response = $this->delete(route('admin.orders.destroy', ['order' => 1]));
        $response->assertStatus(403);
    }

    /**
     * Test qu'un cache stale ne permet pas d'élévation de privilèges
     */
    public function test_stale_cache_does_not_allow_privilege_escalation(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Mettre en cache les permissions admin
        Cache::put("user.{$user->id}.role", 'admin', 3600);
        Cache::put("user.{$user->id}.permissions", ['admin.full'], 3600);

        $this->actingAs($user);

        // Vérifier l'accès admin initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Downgrade vers client
        $user->update(['role' => 'client']);

        // Vérifier que le cache stale ne permet PAS l'accès admin
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Test que le cache est invalidé après suppression d'utilisateur
     */
    public function test_cache_invalidated_after_user_deletion(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
        ]);

        // Mettre en cache
        Cache::put("user.{$user->id}.permissions", ['client'], 3600);

        // Supprimer l'utilisateur (soft delete)
        $user->delete();

        // Vérifier que le cache est invalidé
        $this->assertNull(Cache::get("user.{$user->id}.permissions"));
    }

    /**
     * Test que les changements de permissions en batch invalident tous les caches
     */
    public function test_batch_permission_changes_invalidate_all_caches(): void
    {
        $users = User::factory()->count(3)->create([
            'role' => 'staff',
        ]);

        // Mettre en cache pour tous les utilisateurs
        foreach ($users as $user) {
            Cache::put("user.{$user->id}.role", 'staff', 3600);
        }

        // Changer tous les rôles en batch
        User::whereIn('id', $users->pluck('id'))->update(['role' => 'client']);

        // Vérifier que tous les caches sont invalidés
        foreach ($users as $user) {
            $cachedRole = Cache::get("user.{$user->id}.role");
            // Le cache doit être soit null, soit mis à jour avec 'client'
            $this->assertNotEquals('staff', $cachedRole);
        }
    }

    /**
     * Test que le cache de permissions ne persiste pas après logout
     */
    public function test_permission_cache_cleared_after_logout(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        // Mettre en cache
        Cache::put("user.{$user->id}.permissions", ['admin.full'], 3600);

        // Se déconnecter
        $this->post(route('logout'));

        // Vérifier que le cache est nettoyé
        $this->assertNull(Cache::get("user.{$user->id}.permissions"));
    }

    /**
     * Test que le cache RBAC respecte le TTL configuré
     */
    public function test_rbac_cache_respects_configured_ttl(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Mettre en cache avec TTL court (1 seconde)
        Cache::put("user.{$user->id}.permissions", ['admin'], 1);

        // Vérifier que le cache existe
        $this->assertNotNull(Cache::get("user.{$user->id}.permissions"));

        // Attendre expiration
        sleep(2);

        // Vérifier que le cache a expiré
        $this->assertNull(Cache::get("user.{$user->id}.permissions"));
    }

    /**
     * Test que les permissions héritées sont correctement mises en cache
     */
    public function test_inherited_permissions_correctly_cached(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // Admin hérite de toutes les permissions
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(200);

        // Downgrade vers staff (permissions limitées)
        $admin->update(['role' => 'staff']);

        // Vérifier que les permissions héritées sont mises à jour
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    /**
     * Test que le cache ne cause pas de race condition sur changements simultanés
     */
    public function test_cache_no_race_condition_on_simultaneous_changes(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
        ]);

        $this->actingAs($user);

        // Simuler changements simultanés
        $user->update(['role' => 'admin']);
        Cache::put("user.{$user->id}.role", 'admin', 3600);

        $user->update(['role' => 'client']);
        Cache::put("user.{$user->id}.role", 'client', 3600);

        // Vérifier que le dernier changement prime
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);

        $this->assertEquals('client', $user->fresh()->role);
    }
}
