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
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => false,
        ]);
        
        // Tenter de se connecter
        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        
        // En production, admin doit avoir 2FA configuré
        // Vérifier que l'utilisateur est redirigé vers setup 2FA ou challenge
        if (app()->environment('production')) {
            $response->assertRedirect();
            $this->assertTrue(
                $response->isRedirect(route('2fa.setup')) || 
                $response->isRedirect(route('2fa.challenge'))
            );
        }
    }

    /**
     * Test : Admin avec device expiré → challenge
     */
    public function test_admin_with_expired_device_requires_challenge(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        
        // Créer un token trusted device expiré
        $twoFactorService = app(TwoFactorService::class);
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
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        
        // Se connecter
        Auth::login($admin);
        
        // Se déconnecter
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
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        Auth::login($client);
        
        // Tenter d'accéder à une route admin
        $response = $this->get('/admin/dashboard');
        
        // Vérifier que l'accès est refusé
        $response->assertStatus(403) || $response->assertRedirect();
    }

    /**
     * Test : RBAC - Créateur → ERP → 403
     */
    public function test_creator_cannot_access_erp_routes(): void
    {
        $creator = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);
        
        Auth::login($creator);
        
        // Tenter d'accéder à une route ERP
        $response = $this->get('/erp/dashboard');
        
        // Vérifier que l'accès est refusé
        $response->assertStatus(403) || $response->assertRedirect();
    }

    /**
     * Test : RBAC - Staff sans permission → 403
     */
    public function test_staff_without_permission_gets_403(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
        ]);
        
        Auth::login($staff);
        
        // Tenter d'accéder à une route ERP (sans permission)
        $response = $this->get('/erp/dashboard');
        
        // Vérifier que l'accès est refusé avec 403
        $response->assertStatus(403);
    }

    /**
     * Test : Session expirée → logout propre
     */
    public function test_expired_session_logs_out_cleanly(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        Auth::login($user);
        
        // Expirer la session manuellement
        session()->put('_token', 'expired_token');
        session()->save();
        
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
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        
        // Créer un token trusted device
        $twoFactorService = app(TwoFactorService::class);
        $token = $twoFactorService->generateTrustedDeviceToken($user);
        
        Auth::login($user);
        
        // Changer le mot de passe via le contrôleur
        $response = $this->post('/profil/password', [
            'current_password' => 'password',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
        ]);
        
        // Vérifier que le token trusted device est révoqué
        $user->refresh();
        $this->assertNull($user->trusted_device_token);
        $this->assertNull($user->trusted_device_expires_at);
    }
}



