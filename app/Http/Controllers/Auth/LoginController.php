<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Models\User;
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
    use HandlesAuthRedirect;

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
        $loginContext = $this->resolveLoginContext($request);

        // Passer le contexte à la vue pour adapter l'UI
        return view('auth.login-neutral', [
            'loginContext' => $loginContext,
        ]);
    }

    /**
     * Résout le contexte de connexion depuis la requête et la session
     * 
     * Priorité :
     * 1. Paramètre query `context` si présent et valide
     * 2. Session `login_context` si présente et valide
     * 3. null (contexte neutre)
     * 
     * @param Request $request
     * @return string|null Retourne 'boutique', 'equipe' ou null
     */
    protected function resolveLoginContext(Request $request): ?string
    {
        // Priorité 1: Paramètre query si présent et valide
        $queryContext = $request->query('context');
        
        if ($queryContext && in_array($queryContext, ['boutique', 'equipe'], true)) {
            // Stocker en session pour persistance
            session(['login_context' => $queryContext]);
            return $queryContext;
        }

        // Priorité 2: Session si présente et valide
        $sessionContext = session('login_context');
        
        if ($sessionContext && in_array($sessionContext, ['boutique', 'equipe'], true)) {
            return $sessionContext;
        }

        // Nettoyer la session si contexte invalide
        session()->forget('login_context');

        // Priorité 3: Contexte neutre
        return null;
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

        // Tentative de connexion via le guard 'web'
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Charger la relation roleRelation pour éviter les erreurs
            $user->load('roleRelation');

            // Vérifier le statut de l'utilisateur
            if (isset($user->status) && $user->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Votre compte est désactivé. Contactez l\'administrateur.',
                ])->onlyInput('email');
            }

            // Récupérer le contexte de connexion (optionnel, pour usage futur)
            $context = session('login_context');
            
            // Nettoyer le contexte de la session après utilisation
            session()->forget('login_context');

            // Redirection selon le rôle (le contexte peut être utilisé plus tard pour adapter la redirection)
            return redirect()->intended($this->getRedirectPath($user));
        }

        // Échec de connexion
        throw ValidationException::withMessages([
            'email' => __('Les identifiants fournis sont incorrects.'),
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('frontend.home');
    }
}

