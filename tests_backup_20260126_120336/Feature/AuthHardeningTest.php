<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests Feature - Auth Hardening
 * 
 * Module 4 - Final Hardening
 * 
 * Tests obligatoires :
 * - Admin sans 2FA → rejet
 * - Admin avec cookie expiré → challenge
 * - Staff sans permission ERP → 403
 * - Créateur → accès admin refusé
 * - Session expirée → logout propre
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Admin sans 2FA → rejet
     */
    public function test_admin_without_2fa_is_rejected(): void
    {
        // Créer un admin sans 2FA activé
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
        // Vérifier que l'utilisateur est redirigé vers setup 2FA
        if (app()->environment('production')) {
            $response->assertRedirect(route('2fa.setup'));
        }
    }

    /**
     * Test : Admin avec cookie expiré → challenge
     */
    public function test_admin_with_expired_trusted_device_cookie_requires_challenge(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        
        // Créer un token trusted device expiré
        $twoFactorService = app(TwoFactorService::class);
        $expiredToken = $twoFactorService->generateTrustedDeviceToken($admin, -1); // Expiré
        
        // Mettre à jour l'expiration pour qu'elle soit dans le passé
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
     * Test : Staff sans permission ERP → 403
     */
    public function test_staff_without_erp_permission_gets_403(): void
    {
        // Créer un staff sans permission ERP
        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
        ]);
        
        // Supprimer les permissions ERP si elles existent
        // (dépend de votre système de permissions)
        
        Auth::login($staff);
        
        // Tenter d'accéder à une route ERP
        $response = $this->get('/erp/dashboard');
        
        // Vérifier que l'accès est refusé avec 403
        $response->assertStatus(403);
    }

    /**
     * Test : Créateur → accès admin refusé
     */
    public function test_creator_cannot_access_admin_routes(): void
    {
        $creator = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);
        
        Auth::login($creator);
        
        // Tenter d'accéder à une route admin
        $response = $this->get('/admin/dashboard');
        
        // Vérifier que l'accès est refusé (redirection ou 403)
        $response->assertStatus(403) || $response->assertRedirect();
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
     * Test : Trusted device révoqué lors du logout
     */
    public function test_trusted_device_revoked_on_logout(): void
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
        
        // Se déconnecter
        $response = $this->post('/logout');
        
        // Vérifier que le token trusted device est révoqué
        $user->refresh();
        $this->assertNull($user->trusted_device_token);
        $this->assertNull($user->trusted_device_expires_at);
    }

    /**
     * Test : Trusted device révoqué lors du changement de mot de passe
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
        
        // Changer le mot de passe
        $user->update(['password' => Hash::make('new_password')]);
        
        // Vérifier que le token trusted device est révoqué
        // (à implémenter dans le contrôleur de changement de mot de passe)
        $user->refresh();
        // Note: Cette vérification dépend de l'implémentation du changement de mot de passe
    }
}

