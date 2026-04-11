<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;

class AdminAuthController extends Controller
{
    protected TwoFactorService $twoFactorService;
    
    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }
    
    /**
     * Afficher le formulaire de connexion admin
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Si déjà connecté en tant qu'admin, rediriger vers dashboard
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.login');
    }

    /**
     * Traiter le login admin
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = $request->email;
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Vérifier si le compte est bloqué
        if (LoginAttempt::isBlocked($email, $ipAddress)) {
            $remainingTime = LoginAttempt::getRemainingLockoutTime($email, $ipAddress);
            
            Log::channel('security')->warning('Tentative de connexion admin depuis un compte bloqué', [
                'email' => $email,
                'ip' => $ipAddress,
            ]);

            return back()->withErrors([
                'email' => "Trop de tentatives échouées. Veuillez réessayer dans {$remainingTime} minutes.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Enregistrer tentative réussie
            LoginAttempt::record($email, $ipAddress, $userAgent, true);
            $request->session()->regenerate();
            $user = Auth::user();

            // On vérifie que c'est bien un admin
            if (!$user->isAdmin()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Accès administrateur requis.',
                ]);
            }

            // Vérifier si 2FA est activé
            if ($this->twoFactorService->isEnabled($user)) {
                // En développement local, bypasser la 2FA
                if (app()->environment('local')) {
                    Session::put('2fa_verified', true);
                    return redirect()->route('admin.dashboard');
                }
                
                // Vérifier si appareil de confiance
                $trustedToken = $request->cookie('trusted_device');
                if ($trustedToken && $this->twoFactorService->isTrustedDevice($user, $trustedToken)) {
                    Session::put('2fa_verified', true);
                    return redirect()->route('admin.dashboard');
                }
                
                // Déconnecter et rediriger vers challenge
                Auth::logout();
                Session::put('2fa_user_id', $user->id);
                Session::put('2fa_remember', $request->boolean('remember'));
                Session::put('2fa_redirect', 'admin.dashboard');
                
                return redirect()->route('2fa.challenge');
            }
            
            // Si 2FA obligatoire mais pas configuré
            if ($this->twoFactorService->isRequired($user)) {
                return redirect()->route('2fa.setup')
                    ->with('warning', 'La double authentification est obligatoire pour les administrateurs.');
            }

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Identifiants invalides.',
        ])->onlyInput('email');
    }

    /**
     * Page dashboard admin
     */
    public function dashboard(): View
    {
        $usersCount = User::count();
        $adminsCount = User::where('is_admin', true)
            ->orWhere('role_id', 1)
            ->count();

        return view('admin.dashboard', [
            'usersCount' => $usersCount,
            'adminsCount' => $adminsCount,
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): RedirectResponse
    {
        // Révoquer l'appareil de confiance si demandé
        if ($request->boolean('revoke_trusted_device')) {
            $user = Auth::user();
            if ($user) {
                $this->twoFactorService->revokeTrustedDevice($user);
            }
        }
        
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
