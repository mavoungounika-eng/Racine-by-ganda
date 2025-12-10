<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Stocke le contexte (boutique/equipe) en session pour le callback.
     * 
     * IMPORTANT : Google Login est réservé à l'espace Boutique uniquement.
     */
    public function redirect(Request $request): RedirectResponse
    {
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

        try {
            return Socialite::driver('google')
                ->redirect();
        } catch (\Exception $e) {
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
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            // Récupérer l'utilisateur Google
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Erreur lors de la connexion avec Google. Veuillez réessayer.');
        }

        // Récupérer le contexte depuis la session
        $context = session('social_login_context', 'boutique');
        session()->forget('social_login_context');

        // Si contexte = equipe, refuser la connexion Google
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion Google n\'est pas disponible pour l\'espace équipe. Veuillez utiliser votre email et mot de passe.');
        }

        // Récupérer l'email Google
        $email = $googleUser->getEmail();
        
        if (!$email) {
            return redirect()->route('login')
                ->with('error', 'Impossible de récupérer votre adresse email depuis Google.');
        }

        // Chercher un utilisateur existant par email
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Créer un nouvel utilisateur avec rôle "client" uniquement
            // IMPORTANT : Google Login ne peut créer QUE des comptes client/créateur
            $role = Role::firstOrCreate(
                ['slug' => 'client'],
                [
                    'name' => 'Client',
                    'description' => 'Client standard avec accès aux commandes et au profil.',
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

            // Créer l'utilisateur
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // Mot de passe généré (l'utilisateur pourra le changer)
                'role_id' => $role->id,
                'email_verified_at' => now(), // Email vérifié via Google
            ]);
        } else {
            // Utilisateur existant : charger la relation roleRelation
            $user->load('roleRelation');
            
            // SÉCURITÉ : Vérifier que l'utilisateur existant n'est pas staff/admin
            // Les comptes staff/admin doivent utiliser email + mot de passe uniquement
            $roleSlug = $user->getRoleSlug();
            
            if (in_array($roleSlug, ['staff', 'admin', 'super_admin'], true)) {
                // Refuser la connexion Google pour les comptes équipe
                return redirect()->route('login', ['context' => 'equipe'])
                    ->with('error', 'La connexion Google n\'est pas autorisée pour les comptes équipe. Veuillez utiliser votre email et mot de passe.');
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

        // Rediriger selon le rôle via le trait HandlesAuthRedirect
        return redirect($this->getRedirectPath($user));
    }
}

