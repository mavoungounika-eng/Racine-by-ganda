<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Services\ProfileCompletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CreatorProfileController extends Controller
{
    protected ProfileCompletionService $completionService;

    public function __construct(ProfileCompletionService $completionService)
    {
        $this->completionService = $completionService;
    }

    /**
     * Afficher la page "Mon Profil" complète.
     */
    public function show(): View
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;
        $completion = $this->completionService->calculateCompletionScore($profile);
        
        return view('creator.profile.index', compact('user', 'profile', 'completion'));
    }

    /**
     * Afficher l'aperçu du profil public.
     */
    public function preview(): View
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;
        
        return view('creator.profile.preview', compact('user', 'profile'));
    }

    /**
     * Mettre à jour les informations de la boutique.
     */
    public function updateShop(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
        ]);

        // Logo
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('creators/logos', 'public');
        }

        // Bannière
        if ($request->hasFile('banner')) {
            if ($profile->banner_path) {
                Storage::disk('public')->delete($profile->banner_path);
            }
            $validated['banner_path'] = $request->file('banner')->store('creators/banners', 'public');
        }

        $profile->update($validated);

        return redirect()->route('creator.profile.show')
            ->with('success', 'Informations de la boutique mises à jour avec succès.');
    }

    /**
     * Mettre à jour l'identité du créateur (photo + titre).
     */
    public function updateIdentity(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;

        $validated = $request->validate([
            'creator_title' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        // Avatar
        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('creators/avatars', 'public');
        }

        $profile->update($validated);

        return redirect()->route('creator.profile.show')
            ->with('success', 'Identité mise à jour avec succès.');
    }

    /**
     * Mettre à jour les réseaux sociaux.
     */
    public function updateSocial(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;

        $validated = $request->validate([
            'website' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
        ]);

        $profile->update($validated);

        return redirect()->route('creator.profile.show')
            ->with('success', 'Réseaux sociaux mis à jour avec succès.');
    }
}
