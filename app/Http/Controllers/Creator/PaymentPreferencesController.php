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
     * Page avancée des préférences de paiement
     */
    public function advanced()
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::firstOrCreate(['creator_profile_id' => $creator->id]);

        return view('creator.settings.payment-advanced', compact('creator', 'preferences'));
    }

    /**
     * Initier la connexion Stripe Connect (OAuth)
     */
    public function connectStripe(Request $request)
    {
        $creator = Auth::user()->creatorProfile;

        try {
            $stripeClientId = config('services.stripe.client_id');
            if (!$stripeClientId) {
                return back()->with('error', 'Stripe Connect n\'est pas configuré. Contactez l\'administrateur.');
            }

            $params = http_build_query([
                'response_type' => 'code',
                'client_id'     => $stripeClientId,
                'scope'         => 'read_write',
                'redirect_uri'  => route('creator.settings.payment-preferences.stripe.callback'),
                'state'         => csrf_token(),
            ]);

            return redirect('https://connect.stripe.com/oauth/authorize?' . $params);
        } catch (\Exception $e) {
            Log::error('Stripe Connect OAuth init failed', ['error' => $e->getMessage(), 'creator' => $creator->id]);
            return back()->with('error', 'Erreur lors de l\'initialisation de Stripe Connect.');
        }
    }

    /**
     * Callback OAuth Stripe Connect
     */
    public function stripeCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('creator.settings.payment-preferences.index')
                ->with('error', 'Connexion Stripe annulée : ' . $request->get('error_description'));
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $response = \Stripe\OAuth::token([
                'grant_type' => 'authorization_code',
                'code'       => $request->get('code'),
            ]);

            $creator = Auth::user()->creatorProfile;
            $preferences = PaymentPreference::firstOrCreate(['creator_profile_id' => $creator->id]);
            $preferences->update([
                'stripe_connect_id'        => $response->stripe_user_id,
                'payment_connection_status' => 'connected',
                'last_connection_test_at'  => now(),
            ]);

            return redirect()->route('creator.settings.payment-preferences.index')
                ->with('success', 'Compte Stripe connecté avec succès.');
        } catch (\Exception $e) {
            Log::error('Stripe Connect callback failed', ['error' => $e->getMessage()]);
            return redirect()->route('creator.settings.payment-preferences.index')
                ->with('error', 'Erreur lors de la connexion Stripe : ' . $e->getMessage());
        }
    }

    /**
     * Déconnecter Stripe Connect
     */
    public function disconnectStripe(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        $preferences->update([
            'stripe_connect_id'        => null,
            'stripe_secret_key'        => null,
            'stripe_publishable_key'   => null,
            'payment_connection_status' => 'not_connected',
        ]);

        return back()->with('success', 'Compte Stripe déconnecté.');
    }

    /**
     * Enregistrer les clés Mobile Money (Monetbil)
     */
    public function saveMobileMoney(Request $request)
    {
        $request->validate([
            'momo_provider' => 'required|string',
            'momo_api_key'  => 'required|string',
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::firstOrCreate(['creator_profile_id' => $creator->id]);

        $preferences->update([
            'momo_provider'            => $request->momo_provider,
            'momo_api_key'             => $request->momo_api_key,
            'payment_connection_status' => 'connected',
            'last_connection_test_at'  => now(),
        ]);

        return back()->with('success', 'Configuration Mobile Money enregistrée.');
    }

    /**
     * Supprimer la configuration Mobile Money
     */
    public function deleteMobileMoney(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if ($preferences) {
            $preferences->update([
                'momo_provider' => null,
                'momo_api_key'  => null,
            ]);
        }

        return back()->with('success', 'Configuration Mobile Money supprimée.');
    }

    /**
     * Mettre à jour le calendrier de versement
     */
    public function updateSchedule(Request $request)
    {
        $request->validate(['payout_schedule' => 'required|in:daily,weekly,monthly']);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::firstOrCreate(['creator_profile_id' => $creator->id]);
        $preferences->update(['payout_schedule' => $request->payout_schedule]);

        return back()->with('success', 'Calendrier de versement mis à jour.');
    }

    /**
     * Mettre à jour le seuil de versement
     */
    public function updateThreshold(Request $request)
    {
        $request->validate(['payout_threshold' => 'required|numeric|min:0']);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::firstOrCreate(['creator_profile_id' => $creator->id]);
        $preferences->update(['payout_threshold' => $request->payout_threshold]);

        return back()->with('success', 'Seuil de versement mis à jour.');
    }

    /**
     * Mettre à jour les préférences de notifications
     */
    public function updateNotifications(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::firstOrCreate(['creator_profile_id' => $creator->id]);
        $preferences->update([
            'notify_on_payment'  => $request->boolean('notify_on_payment'),
            'notify_on_payout'   => $request->boolean('notify_on_payout'),
            'notify_on_dispute'  => $request->boolean('notify_on_dispute'),
        ]);

        return back()->with('success', 'Préférences de notifications mises à jour.');
    }

    /**
     * Déconnecter toutes les passerelles
     */
    public function disconnect(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        $preferences->update([
            'stripe_secret_key'        => null,
            'stripe_publishable_key'   => null,
            'momo_provider'            => null,
            'momo_api_key'             => null,
            'payment_connection_status' => 'not_connected',
        ]);

        return back()->with('success', 'Passerelles déconnectées. Vous ne pouvez plus recevoir de paiements directs.');
    }
}
