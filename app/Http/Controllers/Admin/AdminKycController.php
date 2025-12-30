<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorStripeAccount;
use App\Models\User;
use App\Services\Payments\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard KYC pour l'admin
 */
class AdminKycController extends Controller
{
    protected StripeConnectService $stripeService;

    public function __construct(StripeConnectService $stripeService)
    {
        $this->middleware('admin');
        $this->stripeService = $stripeService;
    }

    /**
     * Dashboard KYC - Vue d'ensemble.
     */
    public function index(Request $request): View
    {
        $query = CreatorStripeAccount::with('creatorProfile.user');

        // Filtres
        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'complete') {
                $query->where('onboarding_status', 'complete')
                      ->where('payouts_enabled', true);
            } elseif ($status === 'incomplete') {
                $query->where(function ($q) {
                    $q->where('onboarding_status', '!=', 'complete')
                      ->orWhere('payouts_enabled', false);
                });
            } elseif ($status === 'pending') {
                $query->where('details_submitted', true)
                      ->where('payouts_enabled', false);
            }
        }

        $accounts = $query->latest()->paginate(20);

        // Statistiques
        $stats = [
            'total' => CreatorStripeAccount::count(),
            'complete' => CreatorStripeAccount::where('onboarding_status', 'complete')
                ->where('payouts_enabled', true)->count(),
            'incomplete' => CreatorStripeAccount::where(function ($q) {
                $q->where('onboarding_status', '!=', 'complete')
                  ->orWhere('payouts_enabled', false);
            })->count(),
            'pending' => CreatorStripeAccount::where('details_submitted', true)
                ->where('payouts_enabled', false)->count(),
        ];

        return view('admin.kyc.index', compact('accounts', 'stats'));
    }

    /**
     * Détails KYC d'un créateur.
     */
    public function show(User $creator): View
    {
        $creator->load('creatorProfile.stripeAccount');
        $stripeAccount = $creator->creatorProfile->stripeAccount;

        $kycStatus = null;
        if ($stripeAccount) {
            $kycStatus = [
                'onboarding_complete' => $stripeAccount->onboarding_status === 'complete',
                'details_submitted' => $stripeAccount->details_submitted,
                'payouts_enabled' => $stripeAccount->payouts_enabled,
                'charges_enabled' => $stripeAccount->charges_enabled,
                'requirements' => $stripeAccount->requirements ?? [],
            ];
        }

        return view('admin.kyc.show', compact('creator', 'stripeAccount', 'kycStatus'));
    }

    /**
     * Synchroniser le statut KYC avec Stripe.
     */
    public function sync(User $creator)
    {
        $stripeAccount = $creator->creatorProfile->stripeAccount;

        if (!$stripeAccount) {
            return redirect()->back()
                ->with('error', 'Ce créateur n\'a pas de compte Stripe Connect.');
        }

        try {
            $this->stripeService->syncAccountStatus($stripeAccount);
            
            return redirect()->back()
                ->with('success', 'Statut KYC synchronisé avec succès !');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la synchronisation : ' . $e->getMessage());
        }
    }
}
