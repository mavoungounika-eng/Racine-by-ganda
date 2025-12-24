<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class CreatorSettingsController extends Controller
{
    /**
     * Afficher les paramètres de la vitrine (Storefront).
     */
    public function index(): View
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;
        
        return view('creator.settings.index', compact('user', 'profile'));
    }

    /**
     * Mettre à jour les paramètres de la vitrine.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB max
            'banner' => ['nullable', 'image', 'max:4096'], // 4MB max
        ]);

        // Mise à jour du logo
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('creators/logos', 'public');
        }

        // Mise à jour de la bannière
        if ($request->hasFile('banner')) {
            if ($profile->banner_path) {
                Storage::disk('public')->delete($profile->banner_path);
            }
            $validated['banner_path'] = $request->file('banner')->store('creators/banners', 'public');
        }

        // Slug automatique si changement de nom (optionnel, attention au SEO)
        // Ici on garde le slug stable pour l'instant sauf si vide
        
        $profile->update($validated);

        return redirect()->route('creator.settings.shop')
            ->with('success', 'Vitrine mise à jour avec succès.');
    }

    /**
     * Afficher les préférences de paiement.
     */
    public function payment(): View
    {
        $user = Auth::user();
        $profile = $user->creatorProfile()->with('stripeAccount')->first();
        $stripeAccount = $profile->stripeAccount;
        
        return view('creator.settings.payment', compact('user', 'profile', 'stripeAccount'));
    }

    /**
     * Mettre à jour les préférences de paiement (Mobile Money).
     */
    public function updatePayment(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;

        $validated = $request->validate([
            'payout_method' => ['required', 'in:mobile_money,bank_transfer'],
            'mobile_money_number' => ['required_if:payout_method,mobile_money', 'nullable', 'string'],
            'mobile_money_provider' => ['required_if:payout_method,mobile_money', 'nullable', 'string', 'in:orange,mtn,moov,wave'],
            // Champs banque ignorés pour V1.5
        ]);

        $payoutDetails = $profile->payout_details ?? [];

        if ($validated['payout_method'] === 'mobile_money') {
            $payoutDetails['mobile_money'] = [
                'number' => $validated['mobile_money_number'],
                'provider' => $validated['mobile_money_provider'],
            ];
        }

        $profile->update([
            'payout_method' => $validated['payout_method'],
            'payout_details' => $payoutDetails,
        ]);

        return redirect()->route('creator.settings.payment')
            ->with('success', 'Préférences de paiement mises à jour.');
    }

    /**
     * Afficher l'aperçu du profil public.
     */
    public function showProfile(): View
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;
        
        return view('creator.profile.show', compact('user', 'profile'));
    }
}
