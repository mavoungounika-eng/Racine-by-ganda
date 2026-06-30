<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CreatorDocument;
use App\Services\CreatorAnalyticsService;
use App\Services\CreatorOrderEventService;
use App\Services\CreatorKycContractualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Contrôleur des finances et de la conformité créateur (SaaS Pur).
 * 
 * RACINE BY GANDA n'intervient pas dans les flux monétaires créateurs.
 * Ce contrôleur gère uniquement l'analytique et le KYC contractuel.
 */
class CreatorFinanceController extends Controller
{
    protected CreatorAnalyticsService $analyticsService;
    protected CreatorOrderEventService $orderEventService;
    protected CreatorKycContractualService $kycService;

    public function __construct(
        CreatorAnalyticsService $analyticsService,
        CreatorOrderEventService $orderEventService,
        CreatorKycContractualService $kycService
    ) {
        $this->analyticsService = $analyticsService;
        $this->orderEventService = $orderEventService;
        $this->kycService = $kycService;
    }

    /**
     * Affiche le dashboard analytique du créateur.
     */
    public function index(): View
    {
        $creatorProfile = Auth::user()->creatorProfile;
        
        // Récupération des metrics analytiques (SaaS)
        $metrics = $this->analyticsService->getCreatorMetrics($creatorProfile->id);
        
        // Récupération du statut KYC contractuel
        $kycStatus = $this->kycService->checkContractualStatus($creatorProfile);

        return view('creator.finances.index', [
            'metrics' => $metrics,
            'kycStatus' => $kycStatus,
            'recentSales' => $creatorProfile->saleRecords()->latest()->take(10)->get()
        ]);
    }

    /**
     * Enregistre la remise physique d'une commande via le POS (Événement analytique).
     * 
     * @param Request $request Contient l'ID de la commande
     */
    public function fulfilledByPos(Request $request): RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'pickup_location' => 'required|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        
        // RACINE valide la remise physique sans toucher aux fonds.
        $this->orderEventService->recordOrderFulfilled(
            $order,
            Auth::id(),
            $request->pickup_location
        );

        return redirect()->back()->with('success', 'La commande a été marquée comme remise (Fulfilled).');
    }

    /**
     * Soumet un document pour le KYC contractuel.
     */
    public function submitKycDocument(Request $request): RedirectResponse
    {
        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,png|max:5120',
        ]);

        $profile = Auth::user()->creatorProfile;
        $file = $request->file('file');
        $path = $file->store('creator_documents/' . $profile->id, 'public');

        CreatorDocument::create([
            'creator_profile_id' => $profile->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_verified' => false,
        ]);

        return redirect()->back()->with('success', 'Document soumis pour vérification contractuelle.');
    }
}


