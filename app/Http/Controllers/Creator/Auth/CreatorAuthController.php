<?php

namespace App\Http\Controllers\Creator\Auth;

use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CreatorAuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion créateur.
     */
    public function showLoginForm(): View
    {
        return view('creator.auth.login');
    }

    /**
     * Traiter la connexion créateur.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            
            // Vérifier directement le role_id pour éviter les requêtes SQL lourdes
            // Le rôle créateur a l'ID 4 dans la table roles
            $isCreator = false;
            
            // ✅ Module 8 - Utiliser getRoleSlug() pour cohérence
            $roleSlug = $user->getRoleSlug();
            $isCreator = in_array($roleSlug, ['createur', 'creator']);
            
            if (!$isCreator) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Ces identifiants ne correspondent pas à un compte créateur.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();

            // Charger le profil créateur avec une requête directe pour éviter les problèmes
            $creatorProfile = \App\Models\CreatorProfile::where('user_id', $user->id)->first();
            
            if (!$creatorProfile) {
                // Pas de profil créateur, rediriger vers l'inscription
                Auth::logout();
                return redirect()->route('creator.register')
                    ->with('info', 'Veuillez compléter votre profil créateur.');
            }

            if ($creatorProfile->isPending()) {
                Auth::logout();
                return redirect()->route('creator.login')
                    ->with('status', 'Votre compte créateur est en attente de validation par l\'équipe RACINE.');
            }

            if ($creatorProfile->isSuspended()) {
                Auth::logout();
                return redirect()->route('creator.login')
                    ->with('error', 'Votre compte créateur a été suspendu. Veuillez contacter le support.');
            }

            // Tout est OK, rediriger vers le dashboard
            return redirect()->intended(route('creator.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ])->withInput($request->only('email'));
    }

    /**
     * Afficher le formulaire d'inscription créateur.
     */
    public function showRegisterForm(): View
    {
        return view('creator.auth.register');
    }

    /**
     * Traiter l'inscription créateur.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            
            // Champs du profil créateur
            'brand_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'legal_status' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
        ]);

        // Créer l'utilisateur avec le rôle créateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'createur', // ou 'creator' selon votre convention
        ]);

        // Créer le profil créateur avec statut 'pending'
        CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => $validated['brand_name'],
            'bio' => $validated['bio'] ?? null,
            'location' => $validated['location'] ?? null,
            'website' => $validated['website'] ?? null,
            'instagram_url' => $validated['instagram_url'] ?? null,
            'tiktok_url' => $validated['tiktok_url'] ?? null,
            'type' => $validated['type'] ?? null,
            'legal_status' => $validated['legal_status'] ?? null,
            'registration_number' => $validated['registration_number'] ?? null,
            'status' => 'pending', // En attente de validation
        ]);

        // Ne pas connecter automatiquement, afficher un message
        return redirect()->route('creator.login')
            ->with('success', 'Votre demande de compte créateur a bien été envoyée. Votre compte est en cours de validation par l\'équipe RACINE. Vous recevrez un email une fois votre compte validé.');
    }

    /**
     * Déconnexion créateur.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('creator.login')
            ->with('status', 'Vous avez été déconnecté.');
    }
}
