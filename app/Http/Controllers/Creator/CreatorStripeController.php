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

    public function connect(): RedirectResponse
    {
        $creatorProfile = Auth::user()->creatorProfile;

        $stripeAccount = $this->stripeService->createAccount($creatorProfile);
        $onboardingUrl = $this->stripeService->createOnboardingLink($stripeAccount);

        return redirect()->away($onboardingUrl);
    }

    public function return(): RedirectResponse
    {
        $creatorProfile = Auth::user()->creatorProfile;
        $stripeAccount = $creatorProfile->stripeAccount;

        if ($stripeAccount) {
            $this->stripeService->syncAccountStatus($stripeAccount->stripe_account_id);
        }

        return redirect()->route('creator.settings.payment')
            ->with('success', 'Statut Stripe synchronisé avec succès.');
    }

    public function refresh(): RedirectResponse
    {
        return redirect()->route('creator.settings.payment');
    }
}
