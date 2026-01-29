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
        return redirect()->route('creator.settings.payment-preferences.index')
            ->with('info', 'Le mode de connexion Stripe a changé. Veuillez désormais configurer vos propres clés API.');
    }

    public function return(): RedirectResponse
    {
        return redirect()->route('creator.settings.payment-preferences.index');
    }

    public function refresh(): RedirectResponse
    {
        return redirect()->route('creator.settings.payment-preferences.index');
    }
}
