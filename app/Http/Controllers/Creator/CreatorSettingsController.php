<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentPreference;

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
        $profile = $user->creatorProfile;
        $stripeAccount = $profile->stripeAccount;
        
        // Récupérer ou créer les préférences de versement
        $preferences = PaymentPreference::firstOrCreate(
            ['creator_profile_id' => $profile->id],
            [
                'payout_schedule' => 'automatic',
                'minimum_payout_threshold' => 5000,
                'notify_email' => true,
                'tax_country' => 'CG',
            ]
        );
        
        return view('creator.settings.payment', compact('user', 'profile', 'stripeAccount', 'preferences'));
    }

    /**
     * Mettre à jour les préférences de paiement (Mobile Money).
     */
    public function updatePayment(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $profile->id)->first();

        $validated = $request->validate([
            'payout_method' => ['required', 'in:mobile_money,bank_transfer'],
            'mobile_money_number' => ['required_if:payout_method,mobile_money', 'nullable', 'string', 'regex:/^[0-9]{9,14}$/'],
            'mobile_money_provider' => ['required_if:payout_method,mobile_money', 'nullable', 'string', 'in:orange,mtn,moov,wave'],
            'minimum_payout_threshold' => ['nullable', 'integer', 'min:5000'],
        ]);

        $payoutDetails = $profile->payout_details ?? [];

        if ($validated['payout_method'] === 'mobile_money') {
            $payoutDetails['mobile_money'] = [
                'number' => $validated['mobile_money_number'],
                'provider' => $validated['mobile_money_provider'],
            ];
            
            // Synchronisation avec PaymentPreference
            if ($preferences) {
                $preferences->update([
                    'mobile_money_number' => $validated['mobile_money_number'],
                    'mobile_money_operator' => $validated['mobile_money_provider'],
                    'minimum_payout_threshold' => $request->get('minimum_payout_threshold', $preferences->minimum_payout_threshold),
                ]);
            }
        }

        $profile->update([
            'payout_method' => $validated['payout_method'],
            'payout_details' => $payoutDetails,
        ]);

        return redirect()->route('creator.settings.payment')
            ->with('success', 'Préférences de paiement mises à jour avec succès.');
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
