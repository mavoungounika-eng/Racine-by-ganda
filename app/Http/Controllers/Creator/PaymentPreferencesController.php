<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\PaymentPreference;
use App\Services\Payments\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentPreferencesController extends Controller
{
    protected $stripeConnectService;

    public function __construct(StripeConnectService $stripeConnectService)
    {
        $this->middleware('auth');
        $this->middleware('creator');
        $this->stripeConnectService = $stripeConnectService;
    }

    /**
     * Page principale des préférences de paiement
     */
    public function index()
    {
        $creator = Auth::user()->creatorProfile;
        
        if (!$creator) {
            return redirect()->route('creator.dashboard')
                ->with('error', 'Profil créateur introuvable.');
        }

        // Récupérer ou créer les préférences
        $preferences = PaymentPreference::firstOrCreate(
            ['creator_profile_id' => $creator->id],
            [
                'payout_schedule' => 'automatic',
                'minimum_payout_threshold' => 25000,
                'notify_email' => true,
                'notify_push' => true,
                'tax_country' => 'CG',
            ]
        );

        // Récupérer le compte Stripe
        $stripeAccount = $creator->stripeAccount;

        return view('creator.settings.payment-preferences', compact(
            'creator',
            'preferences',
            'stripeAccount'
        ));
    }

    /**
     * Page des paramètres avancés
     */
    public function advanced()
    {
        $creator = Auth::user()->creatorProfile;
        
        if (!$creator) {
            return redirect()->route('creator.dashboard')
                ->with('error', 'Profil créateur introuvable.');
        }

        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if (!$preferences) {
            return redirect()->route('creator.settings.payment')
                ->with('error', 'Veuillez d\'abord configurer vos préférences de paiement.');
        }

        // Récupérer l'historique des transactions (à implémenter selon votre logique)
        // $recentTransactions = $creator->payouts()->orderBy('created_at', 'desc')->limit(10)->get();
        $recentTransactions = collect(); // Placeholder

        // Calculer le prochain versement
        $nextPayout = $this->calculateNextPayout($preferences);

        return view('creator.settings.payment-advanced', compact(
            'creator',
            'preferences',
            'recentTransactions',
            'nextPayout'
        ));
    }

    /**
     * Initier la connexion Stripe Connect
     */
    public function connectStripe(Request $request)
    {
        $creator = Auth::user()->creatorProfile;

        if (!$creator) {
            return back()->with('error', 'Profil créateur introuvable.');
        }

        try {
            // Vérifier si le créateur a déjà un compte Stripe
            $stripeAccount = $creator->stripeAccount;

            if (!$stripeAccount) {
                // Créer un nouveau compte Stripe Connect
                $stripeAccount = $this->stripeConnectService->createAccount($creator);
            }

            // Créer le lien d'onboarding
            $onboardingLink = $this->stripeConnectService->createOnboardingLink($stripeAccount);

            // Rediriger vers Stripe
            return redirect($onboardingLink->url);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion Stripe Connect', [
                'creator_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la connexion à Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Callback après connexion Stripe
     */
    public function stripeCallback(Request $request)
    {
        $creator = Auth::user()->creatorProfile;

        if (!$creator) {
            return redirect()->route('creator.dashboard')
                ->with('error', 'Profil créateur introuvable.');
        }

        try {
            $stripeAccount = $creator->stripeAccount;

            if (!$stripeAccount) {
                return redirect()
                    ->route('creator.settings.payment')
                    ->with('error', 'Compte Stripe introuvable.');
            }

            // Synchroniser le statut du compte
            $this->stripeConnectService->syncAccountStatus($stripeAccount->stripe_account_id);

            // Recharger le compte après synchronisation
            $stripeAccount->refresh();

            if ($stripeAccount->payouts_enabled) {
                return redirect()
                    ->route('creator.settings.payment')
                    ->with('success', 'Votre compte Stripe a été connecté avec succès ! Vous pouvez maintenant recevoir des paiements.');
            } else {
                return redirect()
                    ->route('creator.settings.payment')
                    ->with('warning', 'Votre compte Stripe nécessite des informations supplémentaires. Veuillez compléter votre profil.');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du callback Stripe', [
                'creator_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('creator.settings.payment')
                ->with('error', 'Erreur lors de la vérification du compte Stripe.');
        }
    }

    /**
     * Enregistrer Mobile Money
     */
    public function saveMobileMoney(Request $request)
    {
        $request->validate([
            'operator' => 'required|in:orange,mtn,wave',
            'phone' => 'required|regex:/^[0-9]{10}$/',
        ], [
            'operator.required' => 'Veuillez sélectionner un opérateur',
            'operator.in' => 'Opérateur non valide',
            'phone.required' => 'Le numéro de téléphone est requis',
            'phone.regex' => 'Le numéro doit contenir exactement 10 chiffres',
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if (!$preferences) {
            return back()->with('error', 'Préférences de paiement introuvables.');
        }

        $preferences->update([
            'mobile_money_operator' => $request->operator,
            'mobile_money_number' => $request->phone,
            'mobile_money_verified' => false, // Nécessite vérification
        ]);

        return back()->with('success', 'Mobile Money enregistré avec succès ! Une vérification sera effectuée prochainement.');
    }

    /**
     * Supprimer Mobile Money
     */
    public function deleteMobileMoney(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if (!$preferences) {
            return back()->with('error', 'Préférences de paiement introuvables.');
        }

        $preferences->update([
            'mobile_money_operator' => null,
            'mobile_money_number' => null,
            'mobile_money_verified' => false,
            'mobile_money_verified_at' => null,
        ]);

        return back()->with('success', 'Mobile Money supprimé avec succès.');
    }

    /**
     * Mettre à jour le calendrier de versement
     */
    public function updateSchedule(Request $request)
    {
        $request->validate([
            'schedule' => 'required|in:automatic,monthly,manual',
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if (!$preferences) {
            return back()->with('error', 'Préférences de paiement introuvables.');
        }

        $preferences->update([
            'payout_schedule' => $request->schedule,
        ]);

        return back()->with('success', 'Calendrier de versement mis à jour avec succès !');
    }

    /**
     * Mettre à jour le seuil minimum
     */
    public function updateThreshold(Request $request)
    {
        $request->validate([
            'threshold' => 'required|integer|min:10000|max:100000',
        ], [
            'threshold.required' => 'Le seuil est requis',
            'threshold.integer' => 'Le seuil doit être un nombre entier',
            'threshold.min' => 'Le seuil minimum est de 10,000 FCFA',
            'threshold.max' => 'Le seuil maximum est de 100,000 FCFA',
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if (!$preferences) {
            return response()->json([
                'success' => false,
                'message' => 'Préférences de paiement introuvables.',
            ], 404);
        }

        $preferences->update([
            'minimum_payout_threshold' => $request->threshold,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seuil mis à jour avec succès',
            'threshold' => number_format($request->threshold, 0, ',', ' ') . ' FCFA',
        ]);
    }

    /**
     * Mettre à jour les préférences de notification
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'notify_email' => 'sometimes|boolean',
            'notify_sms' => 'sometimes|boolean',
            'notify_push' => 'sometimes|boolean',
        ]);

        $creator = Auth::user()->creatorProfile;
        $preferences = PaymentPreference::where('creator_profile_id', $creator->id)->first();

        if (!$preferences) {
            return back()->with('error', 'Préférences de paiement introuvables.');
        }

        $preferences->update([
            'notify_email' => $request->has('notify_email') ? $request->notify_email : $preferences->notify_email,
            'notify_sms' => $request->has('notify_sms') ? $request->notify_sms : $preferences->notify_sms,
            'notify_push' => $request->has('notify_push') ? $request->notify_push : $preferences->notify_push,
        ]);

        return back()->with('success', 'Préférences de notification mises à jour avec succès !');
    }

    /**
     * Déconnecter Stripe (avec confirmation)
     */
    public function disconnectStripe(Request $request)
    {
        $creator = Auth::user()->creatorProfile;
        $stripeAccount = $creator->stripeAccount;

        if (!$stripeAccount) {
            return back()->with('error', 'Aucun compte Stripe à déconnecter.');
        }

        // Vérifier qu'il n'y a pas de paiements en attente (à implémenter selon votre logique)
        // $pendingPayouts = $creator->payouts()->where('status', 'pending')->count();
        // if ($pendingPayouts > 0) {
        //     return back()->with('error', 'Impossible de déconnecter Stripe. Vous avez des versements en attente.');
        // }

        try {
            // Marquer le compte comme inactif (ne pas supprimer pour garder l'historique)
            $stripeAccount->update([
                'onboarding_status' => 'failed',
            ]);

            return redirect()
                ->route('creator.settings.payment')
                ->with('success', 'Votre compte Stripe a été déconnecté. Vous pouvez le reconnecter à tout moment.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion Stripe', [
                'creator_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la déconnexion du compte Stripe.');
        }
    }

    /**
     * Calculer le prochain versement
     */
    private function calculateNextPayout($preferences)
    {
        if (!$preferences) {
            return null;
        }

        switch ($preferences->payout_schedule) {
            case 'automatic':
                return now()->addDays(7)->format('d/m/Y');
            case 'monthly':
                return now()->startOfMonth()->addMonth()->format('d/m/Y');
            case 'manual':
                return 'Sur demande';
            default:
                return null;
        }
    }
}
