<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorStripeAccount;
use App\Services\Payments\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class CreatorStripeController extends Controller
{
    protected StripeConnectService $stripeService;

    public function __construct(StripeConnectService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Initie la connexion avec Stripe Connect.
     */
    public function connect(): RedirectResponse
    {
        $creator = Auth::user()->creatorProfile;

        if (!$creator) {
            return redirect()->back()->with('error', 'Profil créateur introuvable.');
        }

        try {
            // 1. Récupérer ou créer le compte Stripe local
            $stripeAccount = CreatorStripeAccount::where('creator_profile_id', $creator->id)->first();
            
            if (!$stripeAccount) {
                $stripeAccount = $this->stripeService->createAccount($creator);
            }

            // 2. Générer le lien d'onboarding
            $onboardingUrl = $this->stripeService->createOnboardingLink($stripeAccount);

            return redirect()->away($onboardingUrl);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion Stripe Connect : ' . $e->getMessage());
            return redirect()->back()->with('error', 'Impossible d\'initier la connexion avec Stripe. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Retour après l'onboarding Stripe.
     */
    public function return(): RedirectResponse
    {
        $creator = Auth::user()->creatorProfile;
        $stripeAccount = CreatorStripeAccount::where('creator_profile_id', $creator->id)->first();

        if ($stripeAccount) {
            try {
                $this->stripeService->syncAccountStatus($stripeAccount->stripe_account_id);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la synchronisation au retour de Stripe : ' . $e->getMessage());
            }
        }

        return redirect()->route('creator.settings.payment')
            ->with('success', 'Votre compte Stripe a été mis à jour.');
    }

    /**
     * Rafraîchir le lien d'onboarding s'il a expiré.
     */
    public function refresh(): RedirectResponse
    {
        return $this->connect();
    }
}
