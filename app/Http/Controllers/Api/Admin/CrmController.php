<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerSegment;
use App\Models\CustomerTag;
use App\Services\Crm\SegmentationService;
use App\Services\Crm\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CrmController extends Controller
{
    protected $segmentationService;
    protected $loyaltyService;

    public function __construct(SegmentationService $segmentationService, LoyaltyService $loyaltyService)
    {
        $this->segmentationService = $segmentationService;
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Liste des segments avec statistiques.
     */
    public function segments()
    {
        return response()->json([
            'segments' => CustomerSegment::withCount('customers')->get()
        ]);
    }

    /**
     * Détails d'un segment.
     */
    public function segmentDetails(CustomerSegment $segment)
    {
        return response()->json([
            'segment' => $segment,
            'customers' => $segment->customers()->paginate(50)
        ]);
    }

    /**
     * Profil complet d'un client.
     */
    public function customerProfile(User $customer)
    {
        if ($customer->role !== 'client') {
            return response()->json(['error' => 'Not a customer'], 400);
        }

        return response()->json(
            $this->segmentationService::getCustomerProfile($customer)
        );
    }

    /**
     * Ajustement manuel des points de fidélité.
     */
    public function adjustLoyalty(Request $request, User $customer)
    {
        $validated = $request->validate([
            'points'      => 'required|integer',
            'description' => 'required|string|max:255',
        ]);

        $this->loyaltyService->awardPoints(
            $customer,
            $validated['points'],
            'manual',
            null,
            $validated['description']
        );

        return response()->json([
            'message' => 'Points adjusted successfully',
            'new_balance' => $this->loyaltyService->getBalance($customer)
        ]);
    }

    /**
     * Création d'un segment manuel.
     */
    public function storeSegment(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:automatic,manual',
            'rules'       => 'required_if:type,automatic|array',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        $segment = CustomerSegment::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'type'        => $validated['type'],
            'rules'       => $validated['rules'] ?? null,
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? '#3B82F6',
            'created_by'  => auth()->id(),
        ]);

        return response()->json($segment, 201);
    }
}
