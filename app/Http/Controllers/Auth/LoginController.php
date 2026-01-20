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
     * Délègue toute la logique d'authentification à AuthOrchestratorService.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // Déléguer à AuthOrchestratorService
        $orchestrator = app(\App\Services\Auth\AuthOrchestratorService::class);
        $result = $orchestrator->authenticate($request, $credentials, $remember);

        // Gérer le résultat
        if ($result->isFailed()) {
            throw ValidationException::withMessages($result->errors);
        }

        if ($result->requires2FA()) {
            return redirect($result->redirectUrl);
        }

        // Nettoyer le contexte de la session
        $this->clearContext('login');

        return redirect()->intended($result->redirectUrl);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): RedirectResponse
    {
        // Déléguer à AuthOrchestratorService
        $orchestrator = app(\App\Services\Auth\AuthOrchestratorService::class);
        $redirectUrl = $orchestrator->logout($request);

        return redirect($redirectUrl);
    }
}

