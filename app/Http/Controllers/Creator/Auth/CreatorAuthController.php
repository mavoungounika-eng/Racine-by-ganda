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
            'legal_status' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'g-recaptcha-response' => ['required', new \App\Rules\Recaptcha('register_creator')],
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
