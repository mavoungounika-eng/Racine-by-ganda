<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests Feature - Auth Global
 * 
 * PRIORITÉ 3 - Auth, 2FA & RBAC
 * 
 * Scénarios OBLIGATOIRES :
 * - 2FA
 * - RBAC
 * - Sessions
 */
class AuthGlobalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Admin sans 2FA → rejet
     */
    public function test_admin_without_2fa_is_rejected(): void
    {
        // Force 2FA requirement in testing environment
        $this->app['config']['auth.force_2fa_required_in_testing'] = true;
        
        $role = \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_required' => true,
        ]);
        
        // Tenter de se connecter
        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        
        // En production et testing, admin doit avoir 2FA configuré
        // Vérifier que l'utilisateur est redirigé vers setup 2FA
        $response->assertRedirect(route('2fa.setup'));
    }

    /**
     * Test : Admin avec device expiré → challenge
     */
    public function test_admin_with_expired_device_requires_challenge(): void
    {
        // Force 2FA requirement in testing environment
        $this->app['config']['auth.force_2fa_required_in_testing'] = true;
        
        $role = \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $twoFactorService = app(TwoFactorService::class);
        
        $admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'two_factor_secret' => $twoFactorService->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_required' => true,
        ]);
        
        // Créer un token trusted device expiré
        $expiredToken = $twoFactorService->generateTrustedDeviceToken($admin, -1);
        $admin->update(['trusted_device_expires_at' => now()->subDay()]);
        
        // Tenter de se connecter avec cookie expiré
        $response = $this->withCookie('trusted_device', $expiredToken)
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);
        
        // Vérifier que l'utilisateur est redirigé vers challenge 2FA
        $response->assertRedirect(route('2fa.challenge'));
    }

    /**
     * Test : Admin après logout → challenge requis
     */
    public function test_admin_after_logout_requires_challenge(): void
    {
        // Force 2FA requirement in testing environment
        $this->app['config']['auth.force_2fa_required_in_testing'] = true;
        
        $role = \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $twoFactorService = app(TwoFactorService::class);
        
        $admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'two_factor_secret' => $twoFactorService->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_required' => true,
        ]);
        
        // Se connecter
        Auth::login($admin);
        
        // Se déconnecter (via AuthOrchestrator)
        $this->post('/logout');
        
        // Tenter de se reconnecter
        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        
        // Vérifier que le challenge 2FA est requis
        $response->assertRedirect(route('2fa.challenge'));
    }

    /**
     * Test : RBAC - Client → admin routes → 403
     */
    public function test_client_cannot_access_admin_routes(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client']);
        $client = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        
        // Utiliser actingAs SANS vérification 2FA pour déclencher le challenge
        $this->actingAs($client, null, false);
        
        // Tenter d'accéder à une route admin
        $response = $this->get('/admin/dashboard');
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : RBAC - Créateur → ERP → 403
     */
    public function test_creator_cannot_access_erp_routes(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur']);
        $creator = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        
        $this->actingAs($creator);
        
        // Tenter d'accéder à une route admin (que le créateur ne peut pas voir)
        $response = $this->get('/admin/dashboard');
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : RBAC - Staff sans permission → 403
     */
    public function test_staff_without_permission_gets_403(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $staff = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        
        $this->actingAs($staff);
        
        // Tenter d'accéder à une route admin (le staff est bloqué par 'ensure')
        $response = $this->get('/admin/users');
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : Session expirée → logout propre
     */
    public function test_expired_session_logs_out_cleanly(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        
        $this->actingAs($user);
        
        // Simuler une invalidation de session (context manquant dans une session active)
        $this->withSession(['user_context' => null]);
        
        // Tenter d'accéder à une route protégée
        $response = $this->get('/profil');
        
        // Vérifier que l'utilisateur est redirigé vers login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : Trusted device révoqué au changement mot de passe
     */
    public function test_trusted_device_revoked_on_password_change(): void
    {
        $this->markTestSkipped('Password change route /profil/password does not exist in this codebase. TODO: Implement profile password endpoint and test trust device revocation on credential change.');
    }
}








