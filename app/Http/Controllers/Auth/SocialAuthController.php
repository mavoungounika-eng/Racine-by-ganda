<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Services\SocialAuthService;
use App\Exceptions\OAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Contrôleur générique pour l'authentification sociale multi-providers
 * 
 * Supporte : Google, Apple, Facebook
 * 
 * Routes :
 * - GET /auth/{provider}/redirect?role=client|creator&context=boutique
 * - GET /auth/{provider}/callback
 * 
 * Module Social Auth v2 - Indépendant du module Google Auth v1
 */
class SocialAuthController extends Controller
{
    use HandlesAuthRedirect;

    protected SocialAuthService $socialAuthService;

    // Providers autorisés
    protected const ALLOWED_PROVIDERS = ['google', 'apple', 'facebook'];

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Redirige vers le provider OAuth
     * 
     * @param Request $request
     * @param string $provider Provider OAuth (google|apple|facebook)
     * @param string|null $role Rôle demandé : 'client' ou 'creator' (défaut: 'client')
     * @return RedirectResponse
     */
    public function redirect(Request $request, string $provider, ?string $role = 'client'): RedirectResponse
    {
        // Valider le provider
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', 'Provider OAuth non supporté.');
        }

        // Valider et normaliser le rôle
        if (!in_array($role, ['client', 'creator'], true)) {
            $role = 'client';
        }

        // Stocker le rôle en session
        session(['oauth_role' => $role]);

        // Récupérer le contexte
        $context = $request->query('context');

        // SÉCURITÉ : Refuser l'espace équipe
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion sociale n\'est pas disponible pour l\'espace équipe.');
        }

        // Stocker le contexte (uniquement boutique)
        session(['social_login_context' => 'boutique']);

        // Générer et stocker le state CSRF
        $state = Str::random(40);
        session([
            'oauth_state' => $state,
            'oauth_provider' => $provider,
        ]);

        // Vérifier la configuration du provider
        $providerConfig = config("services.{$provider}");
        if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
            Log::warning("OAuth {$provider}: Configuration incomplète");
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', "La connexion {$provider} n'est pas configurée.");
        }

        try {
            // Configuration spécifique selon le provider
            $socialite = Socialite::driver($provider);

            // Apple nécessite des scopes spécifiques
            if ($provider === 'apple') {
                $socialite->scopes(['name', 'email']);
            }

            // Ajouter le state CSRF
            return $socialite
                ->with(['state' => $state])
                ->redirect();
        } catch (\Exception $e) {
            Log::error("OAuth {$provider} redirect error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->forget(['oauth_state', 'oauth_provider', 'oauth_role', 'social_login_context']);
            
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', "La connexion {$provider} n'est pas disponible pour le moment.");
        }
    }

    /**
     * Gère le callback OAuth
     * 
     * @param Request $request
     * @param string $provider Provider OAuth
     * @return RedirectResponse
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        // Valider le provider
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return redirect()->route('login')
                ->with('error', 'Provider OAuth non supporté.');
        }

        // Vérifier le state CSRF
        $sessionState = session('oauth_state');
        $requestState = $request->query('state');
        $sessionProvider = session('oauth_provider');

        if (!$sessionState || $sessionState !== $requestState || $sessionProvider !== $provider) {
            session()->forget(['oauth_state', 'oauth_provider', 'oauth_role', 'social_login_context']);
            return redirect()->route('login')
                ->with('error', 'Erreur de sécurité lors de la connexion. Veuillez réessayer.');
        }

        // Nettoyer le state après validation
        session()->forget(['oauth_state', 'oauth_provider']);

        try {
            // Récupérer l'utilisateur du provider
            $providerUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::error("OAuth {$provider} callback error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login')
                ->with('error', "Erreur lors de la connexion avec {$provider}. Veuillez réessayer.");
        }

        // Récupérer le contexte et le rôle depuis la session
        $context = session('social_login_context', 'boutique');
        $requestedRole = session('oauth_role', 'client');
        session()->forget(['social_login_context', 'oauth_role']);

        // Normaliser le rôle
        $requestedRoleSlug = $requestedRole === 'creator' ? 'createur' : 'client';

        // Refuser l'espace équipe
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion sociale n\'est pas disponible pour l\'espace équipe.');
        }

        // Déléguer la logique métier au service
        try {
            $user = $this->socialAuthService->handleCallback(
                provider: $provider,
                providerUser: $providerUser,
                requestedRole: $requestedRoleSlug,
                context: $context
            );
        } catch (OAuthException $e) {
            return redirect()->route('login', ['context' => $context])
                ->with('error', $e->getMessage())
                ->with('conversion_offer', $e->getConversionOffer());
        } catch (\Exception $e) {
            Log::error("OAuth {$provider} service error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login', ['context' => $context])
                ->with('error', 'Erreur lors de la création de votre compte. Veuillez réessayer.');
        }

        // Vérifier le statut de l'utilisateur
        if (isset($user->status) && $user->status !== 'active') {
            return redirect()->route('login')
                ->with('error', 'Votre compte est désactivé. Contactez l\'administrateur.');
        }

        // Connecter l'utilisateur
        Auth::login($user, true);
        $request->session()->regenerate();

        // Gérer l'onboarding créateur
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['createur', 'creator'])) {
            $creatorProfile = $user->creatorProfile;
            
            if (!$creatorProfile) {
                return redirect()->route('creator.register')
                    ->with('info', 'Veuillez compléter votre profil créateur.');
            }
            
            if ($creatorProfile->isPending()) {
                return redirect()->route('creator.pending')
                    ->with('status', 'Votre compte créateur est en attente de validation.');
            }
            
            if ($creatorProfile->isSuspended()) {
                return redirect()->route('creator.suspended')
                    ->with('error', 'Votre compte créateur a été suspendu.');
            }
        }

        // Rediriger selon le rôle
        return redirect($this->getRedirectPath($user));
    }
}
