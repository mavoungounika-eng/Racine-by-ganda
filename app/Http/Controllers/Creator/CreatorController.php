<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CreatorController extends Controller
{
    /**
     * Afficher le formulaire d'inscription créateur.
     */
    public function showRegistrationForm(): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Si l'utilisateur a déjà un profil créateur, rediriger vers le dashboard
        if ($user->creatorProfile) {
            return redirect()->route('creator.dashboard')
                ->with('info', 'Vous avez déjà un profil créateur.');
        }
        
        return view('creator.register');
    }

    /**
     * Enregistrer un nouveau créateur.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:4096'], // 4MB max
            'banner' => ['nullable', 'image', 'max:4096'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        // Upload de la photo
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('creators/photos', 'public');
            $validated['photo'] = basename($photoPath);
        }

        // Upload de la bannière
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('creators/banners', 'public');
            $validated['banner'] = basename($bannerPath);
        }

        // Créer le profil créateur
        $validated['user_id'] = $user->id;
        CreatorProfile::create($validated);

        // Attribuer le rôle créateur
        $creatorRole = Role::where('name', 'creator')->first();
        if ($creatorRole) {
            $user->role_id = $creatorRole->id;
            $user->save();
        }

        return redirect()->route('creator.dashboard')
            ->with('success', 'Votre profil créateur a été créé avec succès !');
    }

    /**
     * Afficher le profil public d'un créateur.
     */
    public function showPublicProfile(string $slug): View
    {
        $creatorProfile = CreatorProfile::where('slug', $slug)
            ->where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_active', true)->latest()->take(12);
            }, 'collections' => function ($query) {
                $query->where('is_active', true)->latest();
            }])
            ->firstOrFail();

        $productsCount = $creatorProfile->products()->where('is_active', true)->count();
        $collectionsCount = $creatorProfile->collections()->where('is_active', true)->count();

        return view('frontend.creator-profile', compact(
            'creatorProfile',
            'productsCount',
            'collectionsCount'
        ));
    }
}
