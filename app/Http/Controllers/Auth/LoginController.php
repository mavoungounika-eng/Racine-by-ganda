<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Http\Controllers\Auth\Traits\HandlesAuthContext;
use App\Models\User;
use App\Services\AuthLogger;
use App\Services\LoginAttemptService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Contrôleur unifié d'authentification
 * 
 * Gère toutes les connexions (client, créateur, staff, admin, super_admin)
 * via le guard 'web' unique, avec redirection automatique selon le rôle.
 * 
 * Comportement :
 * - /login (GET) : 
 *   * Si connecté → Redirige vers dashboard selon rôle
 *   * Si non connecté → Affiche formulaire login avec UI adaptée selon le contexte (boutique/equipe)
 * 
 * - /login (POST) :
 *   * Valide les identifiants, connecte l'utilisateur
 *   * Redirige vers dashboard selon rôle via getRedirectPath()
 * 
 * Le paramètre `context` (boutique/equipe) est stocké en session et utilisé
 * pour adapter l'UI de la page de login (titres, sous-titres, badge).
 */
class LoginController extends Controller
{
    use HandlesAuthRedirect, HandlesAuthContext;

    public function __construct(
        private AuthLogger $authLogger,
        private LoginAttemptService $attemptService,
        private \App\Services\SessionSecurityService $sessionSecurity
    ) {}

    /**
     * Afficher le formulaire de connexion
     * 
     * Si l'utilisateur est déjà connecté, le redirige vers son dashboard.
     * Sinon, affiche le formulaire et adapte l'UI selon le contexte (boutique/equipe).
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        // Si déjà connecté, rediriger selon le rôle
        if (Auth::check()) {
            $user = Auth::user();
            $user->load('roleRelation');
            
            return redirect($this->getRedirectPath($user));
        }

        // Résoudre le contexte de connexion (boutique, equipe ou null)
        $loginContext = $this->resolveContext($request, 'login');

        // Utiliser la vue premium avec design existant
        return view('auth.login-neutral', [
            'loginContext' => $loginContext,
        ]);
    }



    /**
     * Traiter la connexion
     * 
     * Après une connexion réussie :
     * - Récupère le contexte (boutique/equipe) de la session si présent
     * - Redirige vers le dashboard selon le rôle de l'utilisateur
     * - Le contexte peut être utilisé à l'avenir pour adapter la redirection ou l'UI
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = $request->input('email');
        
        // PHASE 2 SÉCURITÉ : Vérifier CAPTCHA si >= 3 tentatives échouées
        $attempts = $this->attemptService->getAttempts($email);
        if ($attempts >= 3) {
            $request->validate([
                'g-recaptcha-response' => 'required|captcha',
            ], [
                'g-recaptcha-response.required' => 'Veuillez compléter le CAPTCHA.',
                'g-recaptcha-response.captcha' => 'La vérification CAPTCHA a échoué.',
            ]);
            
            $this->authLogger->logCaptchaTriggered($email, $attempts);
        }

        // ✅ Vérifier si le compte est bloqué
        if ($this->attemptService->isLocked($email)) {
            $minutes = $this->attemptService->getRemainingMinutes($email);
            $this->authLogger->logAccountLocked($email, $this->attemptService->getAttempts($email));
            
            // PHASE 1 SÉCURITÉ : Message générique pour empêcher énumération
            throw ValidationException::withMessages([
                'email' => "Identifiants incorrects. Veuillez réessayer dans {$minutes} minute(s).",
            ]);
        }

        // Tentative de connexion via le guard 'web'
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // ✅ Effacer les tentatives échouées après connexion réussie
            $this->attemptService->clearAttempts($email);

            // ✅ Log connexion réussie
            $this->authLogger->logLoginAttempt($user->email, true);

            // Charger la relation roleRelation pour éviter les erreurs
            $user->load('roleRelation');

            // Vérifier le statut de l'utilisateur
            if (isset($user->status) && $user->status !== 'active') {
                Auth::logout();
                // PHASE 1 SÉCURITÉ : Message générique
                return back()->withErrors([
                    'email' => 'Identifiants incorrects. Veuillez réessayer.',
                ])->onlyInput('email');
            }

            // ✅ VÉRIFICATION 2FA pour admin/super_admin (CRITIQUE)
            $twoFactorService = app(\App\Services\TwoFactorService::class);
            $roleSlug = $user->getRoleSlug();
            
            if (in_array($roleSlug, ['admin', 'super_admin'])) {
                // Vérifier si 2FA est activé
                if ($twoFactorService->isEnabled($user)) {
                    // En développement local, bypasser la 2FA (pour faciliter les tests)
                    if (app()->environment('local')) {
                        \Illuminate\Support\Facades\Session::put('2fa_verified', true);
                    } else {
                        // En production : 2FA OBLIGATOIRE
                        // Vérifier si appareil de confiance
                        $trustedToken = $request->cookie('trusted_device');
                        if (!$trustedToken || !$twoFactorService->isTrustedDevice($user, $trustedToken)) {
                            // Déconnecter et rediriger vers challenge
                            Auth::logout();
                            \Illuminate\Support\Facades\Session::put('2fa_user_id', $user->id);
                            \Illuminate\Support\Facades\Session::put('2fa_remember', $request->boolean('remember'));
                            
                            return redirect()->route('2fa.challenge');
                        }
                        // Appareil de confiance valide
                        \Illuminate\Support\Facades\Session::put('2fa_verified', true);
                    }
                } else {
                    // Si 2FA obligatoire mais pas configuré
                    if ($twoFactorService->isRequired($user)) {
                        return redirect()->route('2fa.setup')
                            ->with('warning', 'La double authentification est obligatoire pour les administrateurs.');
                    }
                }
            }

            // PHASE 2 SÉCURITÉ : Initialiser tracking session
            $this->sessionSecurity->initializeSessionTracking($user);
            
            // PHASE 2 SÉCURITÉ : Détecter anomalies (mode passif)
            if ($this->sessionSecurity->isHighValueTarget($user)) {
                $this->sessionSecurity->detectAnomalies($user);
            }

            // Nettoyer le contexte de la session après utilisation
            $this->clearContext('login');

            // Redirection selon le rôle (le contexte peut être utilisé plus tard pour adapter la redirection)
            return redirect()->intended($this->getRedirectPath($user));
        }

        // ✅ Enregistrer la tentative échouée
        $this->attemptService->recordFailedAttempt($email);

        // ✅ Log tentative échouée
        $this->authLogger->logLoginAttempt($request->input('email'), false);
        
        // PHASE 2 SÉCURITÉ : Alerter si 3 échecs sur compte admin
        $attempts = $this->attemptService->getAttempts($email);
        if ($attempts >= 3) {
            // Vérifier si c'est un compte admin
            $targetUser = \App\Models\User::where('email', $email)->first();
            if ($targetUser && $targetUser->isTeamMember()) {
                // Envoyer alerte aux super admins
                $superAdmins = \App\Models\User::where('role', 'super_admin')->get();
                foreach ($superAdmins as $admin) {
                    $admin->notify(new \App\Notifications\SuspiciousLoginAttempt(
                        $email,
                        $request->ip(),
                        $attempts,
                        $request->userAgent()
                    ));
                }
                
                $this->authLogger->logSecurityAlertSent($email, 'suspicious_login_attempts', [
                    'attempts' => $attempts,
                ]);
            }
        }

        // Échec de connexion
        // PHASE 1 SÉCURITÉ : Message générique unifié
        throw ValidationException::withMessages([
            'email' => 'Identifiants incorrects. Veuillez réessayer.',
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // ✅ Log déconnexion
        if ($user) {
            $this->authLogger->logLogout($user);
        }
        
        // ✅ FINAL HARDENING - Révoquer trusted device lors du logout
        if ($user) {
            $twoFactorService = app(\App\Services\TwoFactorService::class);
            $twoFactorService->revokeTrustedDevice($user);
            
            // Supprimer le cookie trusted_device
            cookie()->queue(cookie()->forget('trusted_device'));
        }
        
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('frontend.home');
    }
}

