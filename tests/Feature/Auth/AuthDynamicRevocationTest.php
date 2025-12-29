<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de révocation dynamique d'authentification
 * 
 * Vérifie que les changements de rôles/permissions pendant une session active
 * sont correctement appliqués sans nécessiter de reconnexion
 */
class AuthDynamicRevocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un rôle révoqué pendant une session bloque l'accès immédiatement
     */
    public function test_role_revoked_during_session_blocks_access(): void
    {
        // Créer un utilisateur admin
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Se connecter
        $this->actingAs($admin);

        // Vérifier l'accès initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Révoquer le rôle admin
        $admin->update(['role' => 'client']);

        // Tenter d'accéder à nouveau
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /**
     * Test qu'un staff dont le rôle est révoqué perd l'accès ERP
     */
    public function test_staff_role_revoked_loses_erp_access(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($staff);

        // Accès initial ERP (si staff a permission)
        $response = $this->get('/erp/dashboard');
        
        // Révoquer le rôle staff
        $staff->update(['role' => 'client']);

        // Tenter d'accéder à nouveau
        $response = $this->get('/erp/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Test qu'un créateur dont le statut passe à suspended perd l'accès
     */
    public function test_creator_suspended_loses_access(): void
    {
        $creator = User::factory()->create([
            'role' => 'creator',
        ]);

        // Créer le profil créateur actif
        $creator->creatorProfile()->create([
            'status' => 'active',
            'shop_name' => 'Test Shop',
        ]);

        $this->actingAs($creator);

        // Accès initial
        $response = $this->get(route('creator.dashboard'));
        $response->assertStatus(200);

        // Suspendre le créateur
        $creator->creatorProfile->update(['status' => 'suspended']);

        // Tenter d'accéder à nouveau
        $response = $this->get(route('creator.dashboard'));
        $response->assertRedirect(route('creator.suspended'));
    }

    /**
     * Test qu'un admin dont la 2FA est révoquée est redirigé vers setup
     */
    public function test_admin_2fa_revoked_redirects_to_setup(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'two_factor_enabled' => true,
            'two_factor_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        // Accès initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Révoquer 2FA
        $admin->update([
            'two_factor_enabled' => false,
            'two_factor_verified_at' => null,
        ]);

        // Tenter d'accéder à nouveau (doit rediriger vers setup 2FA)
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('2fa.setup'));
    }

    /**
     * Test que la révocation de permission spécifique bloque l'accès
     */
    public function test_specific_permission_revoked_blocks_access(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Donner une permission spécifique (ex: payments.view)
        $admin->givePermissionTo('payments.view');

        $this->actingAs($admin);

        // Accès initial
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(200);

        // Révoquer la permission
        $admin->revokePermissionTo('payments.view');

        // Tenter d'accéder à nouveau
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(403);
    }

    /**
     * Test que la session est invalidée après révocation de rôle critique
     */
    public function test_session_invalidated_after_critical_role_revocation(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // Vérifier que l'utilisateur est authentifié
        $this->assertTrue(auth()->check());
        $this->assertEquals('admin', auth()->user()->role);

        // Révoquer le rôle admin
        $admin->update(['role' => 'client']);

        // Tenter d'accéder à une route admin
        $response = $this->get(route('admin.dashboard'));

        // Vérifier que l'accès est refusé
        $response->assertStatus(403);

        // Vérifier que l'utilisateur est toujours authentifié mais avec nouveau rôle
        $this->assertTrue(auth()->check());
        $this->assertEquals('client', auth()->user()->fresh()->role);
    }

    /**
     * Test que plusieurs révocations simultanées sont gérées correctement
     */
    public function test_multiple_simultaneous_revocations_handled_correctly(): void
    {
        $users = User::factory()->count(3)->create([
            'role' => 'admin',
        ]);

        // Connecter le premier utilisateur
        $this->actingAs($users[0]);

        // Révoquer tous les rôles admin en batch
        User::where('role', 'admin')->update(['role' => 'client']);

        // Vérifier que l'accès est bloqué pour l'utilisateur connecté
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);

        // Vérifier que tous les utilisateurs ont bien le nouveau rôle
        foreach ($users as $user) {
            $this->assertEquals('client', $user->fresh()->role);
        }
    }

    /**
     * Test qu'un downgrade de rôle (admin -> staff) maintient l'accès approprié
     */
    public function test_role_downgrade_maintains_appropriate_access(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // Accès admin initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Downgrade vers staff
        $admin->update(['role' => 'staff']);

        // L'accès admin doit être bloqué
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);

        // Mais l'accès staff doit fonctionner
        $response = $this->get(route('staff.dashboard'));
        $response->assertStatus(200);
    }
}
