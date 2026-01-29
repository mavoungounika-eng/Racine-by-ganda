<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\PaymentPreference;
use App\Models\CreatorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Balance;

class PaymentPreferencesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'creator']);
    }

    /**
     * Page principale des préférences de paiement (SaaS Pur)
     */
    public function index()
    {
        $creator = Auth::user()->creatorProfile;
        
        if (!$creator) {
            return redirect()->route('creator.dashboard')->with('error', 'Profil créateur introuvable.');
        }

        $preferences = PaymentPreference::firstOrCreate(
            ['creator_profile_id' => $creator->id]
        );

        return view('creator.settings.payment-preferences', compact('creator', 'preferences'));
    }

    /**
     * Enregistrer les clés Stripe Direct
     */
    public function saveStripeKeys(Request $request)
    {
        $request->validate([
            'stripe_secret_key' => 'required|string|starts_with:sk_',
            'stripe_publishable_key' => 'required|string|starts_with:pk_',
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        try {
            // Test de validité de la clé secret
            Stripe::setApiKey($request->stripe_secret_key);
            Balance::retrieve();

            $preferences->update([
                'stripe_secret_key' => $request->stripe_secret_key,
                'stripe_publishable_key' => $request->stripe_publishable_key,
                'payment_connection_status' => 'connected',
                'last_connection_test_at' => now(),
            ]);

            return back()->with('success', 'Configuration Stripe enregistrée et validée avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Clé Stripe invalide : ' . $e->getMessage());
        }
    }

    /**
     * Enregistrer les clés Monetbil Direct
     */
    public function saveMonetbilKeys(Request $request)
    {
        $request->validate([
            'momo_provider' => 'required|string', // ex: service_key
            'momo_api_key' => 'required|string',  // ex: service_secret
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        // Note: Pour Monetbil, on enregistre direct, le test se fait au premier paiement 
        // ou via une implémentation de test API si nécessaire.
        
        $preferences->update([
            'momo_provider' => $request->momo_provider,
            'momo_api_key' => $request->momo_api_key,
            'payment_connection_status' => 'connected',
            'last_connection_test_at' => now(),
        ]);

        return back()->with('success', 'Configuration Monetbil (Mobile Money) enregistrée.');
    }

    /**
     * Déconnecter toutes les passerelles
     */
    public function disconnect(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        $preferences->update([
            'stripe_secret_key' => null,
            'stripe_publishable_key' => null,
            'momo_provider' => null,
            'momo_api_key' => null,
            'payment_connection_status' => 'not_connected',
        ]);

        return back()->with('success', 'Passerelles déconnectées. Vous ne pouvez plus recevoir de paiements directs.');
    }
}
