<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use App\Models\CreatorDocument;
use App\Models\CreatorValidationChecklist;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCreatorController extends Controller
{
    /**
     * Endpoint JSON paginé pour la liste des créateurs (vanilla JS).
     */
    public function dataCreators(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $query = CreatorProfile::with('user:id,name,email,created_at')
            ->withCount('documents');
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', function ($uq) use ($s) {
                    $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                })->orWhere('brand_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }
        $query->orderBy('created_at', 'desc');
        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    /**
     * Bulk — vérifier des créateurs.
     */
    public function bulkVerify(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = CreatorProfile::whereIn('id', $ids)->update(['is_verified' => true, 'status' => 'active']);
        return response()->json(['success' => true, 'message' => $count.' créateur(s) vérifié(s)']);
    }

    /**
     * Bulk — suspendre des créateurs.
     */
    public function bulkSuspend(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = CreatorProfile::whereIn('id', $ids)->update(['status' => 'suspended']);
        return response()->json(['success' => true, 'message' => $count.' créateur(s) suspendu(s)']);
    }

    /**
     * Afficher la liste des créateurs avec filtres.
     */
    public function index(Request $request): View
    {
        $query = CreatorProfile::with('user')
            ->withCount([
                'products',
                'documents',
                'documents as verified_documents_count' => function ($query) {
                    $query->where('is_verified', true);
                }
            ]);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('brand_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filtre par vérification
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        $creators = $query->latest()->paginate(20)->withQueryString();

        return view('admin.creators.index', compact('creators'));
    }

    /**
     * Afficher les détails d'un créateur.
     */
    public function show($id): View
    {
        $creator = CreatorProfile::with([
            'user', 
            'products', 
            'documents.verifier', 
            'validationChecklist.completedByUser',
            'activityLogs.user',
            'adminNotes.creator',
            'validationSteps.assignedUser',
            'validationSteps.approver'
        ])
            ->withCount([
                'documents',
                'documents as verified_documents_count' => function ($query) {
                    $query->where('is_verified', true);
                }
            ])
            ->findOrFail($id);

        // Trier les notes : épinglées en premier, puis importantes, puis par date
        $creator->load(['adminNotes' => function ($query) {
            $query->orderBy('is_pinned', 'desc')
                  ->orderBy('is_important', 'desc')
                  ->orderBy('created_at', 'desc');
        }]);

        return view('admin.creators.show', compact('creator'));
    }

    /**
     * Vérifier ou retirer la vérification d'un créateur.
     */
    public function verify($id)
    {
        $creator = CreatorProfile::findOrFail($id);
        $oldVerified = $creator->is_verified;
        $creator->update(['is_verified' => !$creator->is_verified]);

        $message = $creator->is_verified 
            ? 'Créateur vérifié avec succès.' 
            : 'Vérification retirée avec succès.';

        // L'activité sera enregistrée automatiquement par l'observer

        return redirect()->back()->with('success', $message);
    }

    /**
     * Vérifier un document d'un créateur.
     */
    public function verifyDocument($id)
    {
        $document = CreatorDocument::findOrFail($id);
        
        $document->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Document vérifié avec succès.');
    }

    /**
     * Initialiser la checklist pour un créateur.
     */
    public function initializeChecklist($id)
    {
        $creator = CreatorProfile::findOrFail($id);
        CreatorValidationChecklist::initializeForCreator($creator->id);

        return redirect()->back()->with('success', 'Checklist initialisée avec succès.');
    }

    /**
     * Marquer un item de checklist comme complété.
     */
    public function completeChecklistItem($id)
    {
        $item = CreatorValidationChecklist::findOrFail($id);
        
        $item->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        // Enregistrer l'activité
        \App\Models\CreatorActivityLog::log(
            $item->creator_profile_id,
            auth()->id(),
            'checklist_completed',
            "Élément \"{$item->item_label}\" marqué comme complété"
        );

        return redirect()->back()->with('success', 'Élément marqué comme complété.');
    }

    /**
     * Marquer un item de checklist comme non complété.
     */
    public function uncompleteChecklistItem($id)
    {
        $item = CreatorValidationChecklist::findOrFail($id);
        
        $item->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        // Enregistrer l'activité
        \App\Models\CreatorActivityLog::log(
            $item->creator_profile_id,
            auth()->id(),
            'checklist_uncompleted',
            "Élément \"{$item->item_label}\" marqué comme non complété"
        );

        return redirect()->back()->with('success', 'Élément marqué comme non complété.');
    }
}
