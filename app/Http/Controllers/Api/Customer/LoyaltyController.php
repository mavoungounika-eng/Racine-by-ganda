<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Crm\LoyaltyService;
use App\Models\LoyaltyPoint;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    protected $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Statut de fidélité de l'utilisateur connecté.
     */
    public function status()
    {
        $user = auth()->user();

        return response()->json([
            'balance' => $this->loyaltyService->getBalance($user),
            'level'   => $this->loyaltyService->getCurrentLevel($user),
            'history' => LoyaltyPoint::where('customer_id', $user->id)
                            ->latest()
                            ->limit(20)
                            ->get()
        ]);
    }
}
