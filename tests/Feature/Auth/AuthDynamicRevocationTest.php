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

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Roles for sync logic
        \App\Models\Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_active' => true]);
        \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_active' => true]);
        \App\Models\Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'is_active' => true]);
        \App\Models\Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'is_active' => true]);
        \App\Models\Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
        
        // Seed Plans required for dashboard
        $this->seed(\Database\Seeders\CreatorPlanSeeder::class);
    }

    /**
     * Test qu'un rôle révoqué pendant une session bloque l'accès immédiatement
     */
    public function test_role_revoked_during_session_blocks_access(): void
    {
        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->first()->id,
            'is_admin' => true,
            'role' => 'admin',
            'auth_version' => 1,
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->refresh();

        // Se connecter
        $this->actAsWithContext($admin);

        // Vérifier l'accès initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Révoquer le rôle admin
        $admin->update(['role' => 'client']);
        
        // Rafraîchir l'instance pour simuler une nouvelle requête
        $admin->refresh();
        $this->actAsWithContext($admin); // Utiliser le contexte pour valider la session

        // Tenter d'accéder à nouveau
        // La validation de session échoue (auth_version mismatch) -> déconnexion et redirect login
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test qu'un staff dont le rôle est révoqué perd l'accès ERP
     */
    public function test_staff_role_revoked_loses_erp_access(): void
    {
        $staff = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'staff')->first()->id,
            'role' => 'staff',
            'auth_version' => 1,
        ]);
        $staff->refresh();

        $this->actAsWithContext($staff);

        // Accès initial ERP (si staff a permission)
        $response = $this->get('/erp/dashboard');
        
        // Révoquer le rôle staff
        $staff->update(['role' => 'client']);
        
        // Rafraîchir l'instance
        $staff->refresh();
        $this->actAsWithContext($staff);

        // Tenter d'accéder à nouveau -> redirect (session invalidée)
        $response = $this->get(route('erp.dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test qu'un créateur dont le statut passe à suspended perd l'accès
     */
    public function test_creator_suspended_loses_access(): void
    {
        $creator = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'createur')->first()->id,
            'role' => 'createur',
            'auth_version' => 1,
        ]);

        // Créer le profil créateur actif
        $creator->creatorProfile()->create([
            'status' => 'active',
            'brand_name' => 'Test Shop',
        ]);

        $creator->refresh();
        $this->actAsWithContext($creator);

        // Accès initial
        $response = $this->get(route('creator.dashboard'));
        $response->assertStatus(200);

        // Suspendre le créateur
        $creator->creatorProfile->update(['status' => 'suspended']);
        
        // Rafraîchir l'instance
        $creator->refresh();
        
        // Note: l'observer met à jour auth_version su User, donc on refresh User aussi
        $creator = $creator->fresh();
        $this->actAsWithContext($creator);

        // Tenter d'accéder à nouveau -> 302 (Session invalidée ou redirect suspended)
        $response = $this->get(route('creator.dashboard'));
        $response->assertStatus(302); // Login redirect
    }

    /**
     * Test qu'un admin dont la 2FA est révoquée est redirigé vers setup
     */
    public function test_admin_2fa_revoked_redirects_to_setup(): void
    {
        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->first()->id,
            'is_admin' => true,
            'role' => 'admin',
            'auth_version' => 1,
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->refresh();

        $this->actAsWithContext($admin);

        // Accès initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Révoquer 2FA
        $admin->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);
        
        $admin->refresh();
        $this->actAsWithContext($admin);

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
            'role_id' => \App\Models\Role::where('slug', 'admin')->first()->id,
            'is_admin' => true,
            'role' => 'admin',
            'auth_version' => 1,
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ]);

        // Donner une permission spécifique (ex: view-orders pour le gate payments.view)
        $permission = \App\Models\Permission::firstOrCreate(['slug' => 'view-orders'], ['name' => 'View Orders']);
        $admin->roleRelation->permissions()->attach($permission->id);
        
        $admin->refresh();

        $this->actAsWithContext($admin);

        // Accès initial
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(200);

        // Révoquer la permission du RÔLE (car c'est du RBAC strict)
        $admin->roleRelation->permissions()->detach($permission->id);
        
        // Rafraîchir l'instance et ses relations
        $admin->refresh();
        $admin->load('roleRelation.permissions');
        
        $this->actAsWithContext($admin);

        // Tenter d'accéder à nouveau -> Redirection 302 vers login
        $response = $this->get(route('admin.payments.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test que la session est invalidée après révocation de rôle critique
     */
    public function test_session_invalidated_after_critical_role_revocation(): void
    {
        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->first()->id,
            'is_admin' => true,
            'role' => 'admin',
            'auth_version' => 1,
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->refresh();

        $this->actAsWithContext($admin);

        // Vérifier que l'utilisateur est authentifié
        $this->assertTrue(auth()->check());
        $this->assertEquals('admin', auth()->user()->role);

        // Révoquer le rôle admin
        $admin->update(['role' => 'client']);
        
        $admin->refresh();
        $this->actAsWithContext($admin);

        // Tenter d'accéder à une route admin
        $response = $this->get(route('admin.dashboard'));

        // Vérifier que l'accès est refusé (Redirection login car session invalidée par EnsureAuthenticated)
        $response->assertRedirect(route('login'));

        // Vérifier que l'utilisateur est toujours authentifié mais avec nouveau rôle (si on n'avait pas logout)
        // Mais EnsureAuthenticated LOGOUT maintenant.
        $this->assertFalse(auth()->check());
    }

    /**
     * Test que plusieurs révocations simultanées sont gérées correctement
     */
    public function test_multiple_simultaneous_revocations_handled_correctly(): void
    {
        $users = User::factory()->count(3)->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->first()->id,
            'is_admin' => true,
            'role' => 'admin',
            'auth_version' => 1,
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ]);
        
        foreach($users as $user) { $user->refresh(); }

        // Connecter le premier utilisateur
        $this->actAsWithContext($users[0]);

        // Révoquer tous les rôles admin en batch
        User::where('role', 'admin')->update(['role' => 'client']); // Note: ceci ne trigger pas les observers Eloquent en batch!
        
        // Pour le test, on update individuellement pour trigger les observers (comme via UI admin)
        // Ou on simule l'update qui incrémente auth_version manuellement si on fait du bulk
        foreach($users as $user) {
            $user->update(['role' => 'client']);
            $user->refresh();
        }
        
        // Rafraîchir l'utilisateur connecté
        $users[0]->refresh();
        $this->actAsWithContext($users[0]);

        // Vérifier que l'accès est bloqué -> redirect
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));

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
            'role_id' => \App\Models\Role::where('slug', 'admin')->first()->id,
            'is_admin' => true,
            'role' => 'admin',
            'auth_version' => 1,
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ]);
        
        $admin->refresh();

        $this->actAsWithContext($admin);

        // Accès admin initial
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Downgrade vers staff
        $admin->update(['role' => 'staff']);

        // L'accès admin doit être bloqué -> redirect (changement de rôle)
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));

        // Mais l'accès staff doit fonctionner (si on se reconnecte)
        // Ici on simule une reconnexion propre avec le nouveau rôle
        $this->actAsWithContext($admin->fresh());
        $response = $this->get(route('staff.dashboard'));
        $response->assertStatus(200);
    }
}
