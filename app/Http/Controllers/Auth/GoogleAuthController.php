<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Models\User;
use App\Models\Role;
use App\Models\CreatorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Contrôleur pour l'authentification Google via Socialite
 * 
 * Gère :
 * - La redirection vers Google OAuth
 * - Le callback Google avec création/connexion utilisateur
 * - Le contexte (boutique/equipe) pour adapter le comportement
 * 
 * Règles :
 * - Google Login réservé aux comptes "client" (contexte boutique/neutral)
 * - Contexte "equipe" → redirection vers login avec message d'erreur
 */
class GoogleAuthController extends Controller
{
    use HandlesAuthRedirect;

    /**
     * Redirige vers Google OAuth
     * 
     * Stocke le contexte (boutique/equipe) et le rôle en session pour le callback.
     * 
     * PHASE 1.2 : Protection CSRF via paramètre state
     * PHASE 2.1 : Gestion du paramètre role (client|creator)
     * 
     * IMPORTANT : Google Login est réservé à l'espace Boutique uniquement.
     * 
     * @param Request $request
     * @param string|null $role Rôle demandé : 'client' ou 'creator' (défaut: 'client')
     */
    public function redirect(Request $request, ?string $role = 'client'): RedirectResponse
    {
        // PHASE 2.1 : Valider et normaliser le rôle
        if (!in_array($role, ['client', 'creator'], true)) {
            $role = 'client'; // Valeur par défaut
        }
        
        // PHASE 2.1 : Stocker le rôle en session pour le callback
        session(['google_auth_role' => $role]);
        
        // Récupérer le contexte depuis la query string
        $context = $request->query('context');
        
        // SÉCURITÉ : Si contexte = equipe, refuser immédiatement
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion Google n\'est pas disponible pour l\'espace équipe. Veuillez utiliser votre email et mot de passe.');
        }
        
        // Valider et stocker le contexte en session (uniquement boutique)
        if ($context === 'boutique') {
            session(['social_login_context' => 'boutique']);
        } else {
            // Contexte neutre ou non spécifié = boutique par défaut
            session(['social_login_context' => 'boutique']);
        }

        // PHASE 1.2 : Générer et stocker le state pour protection CSRF
        $state = Str::random(40);
        session(['oauth_state' => $state]);

        // Vérifier que la configuration Google OAuth est complète
        $googleConfig = config('services.google');
        if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
            \Log::warning('Google OAuth: Configuration incomplète', [
                'client_id_set' => !empty($googleConfig['client_id']),
                'client_secret_set' => !empty($googleConfig['client_secret']),
            ]);
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', 'La connexion Google n\'est pas configurée. Contactez l\'administrateur.');
        }

        try {
            return Socialite::driver('google')
                ->with(['state' => $state])
                ->redirect();
        } catch (\Exception $e) {
            // Nettoyer le state en cas d'erreur
            session()->forget('oauth_state');
            
            // Si Google n'est pas configuré, rediriger vers login avec erreur
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', 'La connexion Google n\'est pas disponible pour le moment.');
        }
    }

    /**
     * Gère le callback Google OAuth
     * 
     * - Récupère les infos Google
     * - Trouve ou crée un utilisateur avec rôle "client"
     * - Connecte l'utilisateur
     * - Redirige selon le rôle
     * 
     * PHASE 1.2 : Vérification du paramètre state pour protection CSRF
     */
    public function callback(Request $request): RedirectResponse
    {
        // PHASE 1.2 : Vérifier le state OAuth pour prévenir CSRF/OAuth replay
        $sessionState = session('oauth_state');
        $requestState = $request->query('state');
        
        if (!$sessionState || $sessionState !== $requestState) {
            session()->forget('oauth_state');
            return redirect()->route('login')
                ->with('error', 'Erreur de sécurité lors de la connexion. Veuillez réessayer.');
        }
        
        // Supprimer le state après validation
        session()->forget('oauth_state');

        try {
            // Récupérer l'utilisateur Google
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Erreur lors de la connexion avec Google. Veuillez réessayer.');
        }

        // Récupérer le contexte et le rôle depuis la session
        $context = session('social_login_context', 'boutique');
        session()->forget('social_login_context');
        
        // PHASE 2.1 : Récupérer le rôle demandé depuis la session
        $requestedRole = session('google_auth_role', 'client');
        session()->forget('google_auth_role');
        
        // Normaliser le rôle (creator → createur pour la base de données)
        $requestedRoleSlug = $requestedRole === 'creator' ? 'createur' : 'client';

        // Si contexte = equipe, refuser la connexion Google
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion Google n\'est pas disponible pour l\'espace équipe. Veuillez utiliser votre email et mot de passe.');
        }

        // Récupérer l'email et l'ID Google
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        
        if (!$email) {
            return redirect()->route('login')
                ->with('error', 'Impossible de récupérer votre adresse email depuis Google.');
        }

        // PHASE 1.3 : Liaison fiable compte Google ↔ utilisateur
        // Vérifier d'abord si un utilisateur existe avec ce google_id
        $userByGoogleId = User::where('google_id', $googleId)->first();
        
        if ($userByGoogleId) {
            // Un utilisateur existe déjà avec ce google_id
            // Vérifier que l'email correspond
            if ($userByGoogleId->email !== $email) {
                // Incohérence : google_id lié à un autre email
                return redirect()->route('login')
                    ->with('error', 'Ce compte Google est déjà associé à un autre compte. Contactez le support si vous pensez qu\'il s\'agit d\'une erreur.');
            }
            
            // Tout est cohérent, utiliser cet utilisateur
            $user = $userByGoogleId;
            $user->load('roleRelation');
        } else {
            // Aucun utilisateur avec ce google_id, chercher par email
            $user = User::where('email', $email)->first();
            
            if ($user) {
                // Utilisateur existant par email
                $user->load('roleRelation');
                
                // PHASE 1.3 : Vérifier la cohérence de la liaison
                if ($user->google_id && $user->google_id !== $googleId) {
                    // google_id existe et est différent → refus (account takeover)
                    return redirect()->route('login')
                        ->with('error', 'Cet email est déjà associé à un autre compte Google. Veuillez utiliser votre email et mot de passe pour vous connecter.');
                }
                
                // PHASE 2.2 : Gestion stricte des conflits de rôle
                $currentRoleSlug = $user->getRoleSlug();
                
                // Normaliser les rôles pour comparaison
                $currentRoleNormalized = $currentRoleSlug === 'createur' ? 'creator' : ($currentRoleSlug === 'creator' ? 'creator' : 'client');
                $requestedRoleNormalized = $requestedRole;
                
                if ($currentRoleNormalized !== $requestedRoleNormalized) {
                    // PHASE 2.2 : Conflit de rôle → refus avec message explicite
                    $currentRoleLabel = $currentRoleSlug === 'createur' || $currentRoleSlug === 'creator' ? 'créateur' : 'client';
                    $requestedRoleLabel = $requestedRole === 'creator' ? 'créateur' : 'client';
                    
                    return redirect()->route('login')
                        ->with('error', "Un compte existe déjà avec cet email avec le rôle {$currentRoleLabel}. Vous avez tenté de vous connecter en tant que {$requestedRoleLabel}.")
                        ->with('conversion_offer', [
                            'email' => $email,
                            'from_role' => $currentRoleSlug,
                            'to_role' => $requestedRoleSlug,
                        ]);
                }
                
                // PHASE 1.3 : Lier le compte Google si pas encore lié
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleId]);
                }
                
                // SÉCURITÉ : Vérifier que l'utilisateur existant n'est pas staff/admin
                // Les comptes staff/admin doivent utiliser email + mot de passe uniquement
                if (in_array($currentRoleSlug, ['staff', 'admin', 'super_admin'], true)) {
                    // Refuser la connexion Google pour les comptes équipe
                    return redirect()->route('login', ['context' => 'equipe'])
                        ->with('error', 'La connexion Google n\'est pas autorisée pour les comptes équipe. Veuillez utiliser votre email et mot de passe.');
                }
            } else {
                // PHASE 3.1 : Création atomique utilisateur + profil créateur (transaction)
                // Nouvel utilisateur : créer avec google_id et le rôle demandé
                // PHASE 2.1 : Utiliser le rôle demandé depuis la session
                $roleName = $requestedRoleSlug === 'createur' ? 'Créateur' : 'Client';
                $roleDescription = $requestedRoleSlug === 'createur' 
                    ? 'Créateur avec accès à la marketplace et au dashboard créateur.'
                    : 'Client standard avec accès aux commandes et au profil.';
                
                $role = Role::firstOrCreate(
                    ['slug' => $requestedRoleSlug],
                    [
                        'name' => $roleName,
                        'description' => $roleDescription,
                        'is_active' => true,
                    ]
                );

                // Générer un nom depuis les infos Google
                $name = $googleUser->getName();
                if (!$name) {
                    // Extraire le nom depuis l'email si pas de nom
                    $name = explode('@', $email)[0];
                    $name = ucfirst(str_replace(['.', '_', '-'], ' ', $name));
                }

                // PHASE 3.1 : Transaction atomique pour création utilisateur + profil créateur
                try {
                    $user = DB::transaction(function () use ($name, $email, $googleId, $role, $requestedRoleSlug) {
                        // PHASE 1.3 + PHASE 2.1 : Créer l'utilisateur avec google_id et le rôle demandé
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'google_id' => $googleId, // PHASE 1.3 : Stocker le google_id
                            'password' => Hash::make(Str::random(32)), // Mot de passe généré (l'utilisateur pourra le changer)
                            'role_id' => $role->id,
                            'email_verified_at' => now(), // Email vérifié via Google
                        ]);
                        
                        // PHASE 3.1 : Si rôle créateur, créer le profil créateur avec statut pending
                        if ($requestedRoleSlug === 'createur') {
                            CreatorProfile::create([
                                'user_id' => $user->id,
                                'brand_name' => $name, // Données minimales, complétion lors de l'onboarding
                                'status' => 'pending', // En attente de validation
                                'is_active' => false, // Inactif jusqu'à validation
                                'is_verified' => false,
                            ]);
                        }
                        
                        return $user;
                    });
                } catch (\Exception $e) {
                    // PHASE 3.1 : Rollback automatique en cas d'erreur
                    return redirect()->route('login')
                        ->with('error', 'Erreur lors de la création de votre compte. Veuillez réessayer.');
                }
            }
        }

        // S'assurer que roleRelation est chargé
        if (!$user->relationLoaded('roleRelation')) {
            $user->load('roleRelation');
        }

        // Vérifier le statut de l'utilisateur
        if (isset($user->status) && $user->status !== 'active') {
            return redirect()->route('login')
                ->with('error', 'Votre compte est désactivé. Contactez l\'administrateur.');
        }

        // Connecter l'utilisateur
        Auth::login($user, true); // "Se souvenir" activé par défaut

        // Régénérer la session
        $request->session()->regenerate();

        // PHASE 3.2 : Onboarding post-Google créateur (redirection obligatoire)
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['createur', 'creator'])) {
            // Vérifier si le profil créateur existe et son statut
            $creatorProfile = $user->creatorProfile;
            
            if (!$creatorProfile) {
                // Pas de profil créateur → rediriger vers l'inscription créateur
                return redirect()->route('creator.register')
                    ->with('info', 'Veuillez compléter votre profil créateur.');
            }
            
            if ($creatorProfile->isPending()) {
                // Profil en attente de validation → rediriger vers la page pending
                return redirect()->route('creator.pending')
                    ->with('status', 'Votre compte créateur est en attente de validation par l\'équipe RACINE.');
            }
            
            if ($creatorProfile->isSuspended()) {
                // Profil suspendu → rediriger vers la page suspended
                return redirect()->route('creator.suspended')
                    ->with('error', 'Votre compte créateur a été suspendu. Veuillez contacter le support.');
            }
        }

        // Rediriger selon le rôle via le trait HandlesAuthRedirect
        return redirect($this->getRedirectPath($user));
    }
}

