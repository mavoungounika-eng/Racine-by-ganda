<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminActionDecision;
use App\Models\CreatorProfile;
use App\Services\Action\ActionExecutionService;
use App\Services\Action\ActionProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur Admin - Gestion des Actions
 * 
 * Phase 8.4 - Interface admin pour file d'actions
 */
class ActionController
{
    protected ActionProposalService $proposalService;
    protected ActionExecutionService $executionService;

    public function __construct(
        ActionProposalService $proposalService,
        ActionExecutionService $executionService
    ) {
        $this->proposalService = $proposalService;
        $this->executionService = $executionService;
    }

    /**
     * Obtenir les actions en attente
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $actionType = $request->input('action_type');
        $riskLevel = $request->input('risk_level');

        $query = AdminActionDecision::pending()
            ->orderBy('proposed_at', 'desc');

        if ($actionType) {
            $query->where('action_type', $actionType);
        }

        if ($riskLevel) {
            $query->where('risk_level', $riskLevel);
        }

        $actions = $query->limit($limit)->get();

        return response()->json([
            'actions' => $actions,
            'total_count' => $actions->count(),
        ]);
    }

    /**
     * Proposer des actions pour un créateur
     * 
     * @param Request $request
     * @param int $creatorId
     * @return JsonResponse
     */
    public function proposeForCreator(Request $request, int $creatorId): JsonResponse
    {
        $creator = CreatorProfile::findOrFail($creatorId);

        // Générer les propositions
        $proposals = $this->proposalService->proposeActions($creator);

        // Créer les enregistrements AdminActionDecision
        $created = [];
        foreach ($proposals['proposals'] as $proposal) {
            $actionDecision = AdminActionDecision::create([
                'action_type' => $proposal['action'],
                'target_type' => $proposal['target_type'],
                'target_id' => $proposal['target_id'],
                'proposed_by' => auth()->id(),
                'status' => 'pending',
                'confidence' => $proposal['confidence'] ?? null,
                'risk_level' => $proposal['risk_level'] ?? null,
                'justification' => $proposal['justification'],
                'source_data' => $proposal['source_data'] ?? [],
            ]);

            $created[] = $actionDecision;
        }

        return response()->json([
            'proposals' => $proposals,
            'created_actions' => $created,
            'message' => count($created) . ' action(s) proposed',
        ], 201);
    }

    /**
     * Approuver une action
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision_reason' => 'required|string|min:10',
        ]);

        $actionDecision = AdminActionDecision::findOrFail($id);

        if ($actionDecision->status !== 'pending') {
            return response()->json([
                'error' => 'Action is not pending',
            ], 400);
        }

        $actionDecision->approve(
            auth()->id(),
            $request->input('decision_reason')
        );

        return response()->json([
            'message' => 'Action approved',
            'action' => $actionDecision,
        ]);
    }

    /**
     * Rejeter une action
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision_reason' => 'required|string|min:10',
        ]);

        $actionDecision = AdminActionDecision::findOrFail($id);

        if ($actionDecision->status !== 'pending') {
            return response()->json([
                'error' => 'Action is not pending',
            ], 400);
        }

        $actionDecision->reject(
            auth()->id(),
            $request->input('decision_reason')
        );

        return response()->json([
            'message' => 'Action rejected',
            'action' => $actionDecision,
        ]);
    }

    /**
     * Exécuter une action approuvée
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function execute(Request $request, int $id): JsonResponse
    {
        $actionDecision = AdminActionDecision::findOrFail($id);

        if (!$actionDecision->canBeExecuted()) {
            return response()->json([
                'error' => 'Action cannot be executed. Status: ' . $actionDecision->status,
            ], 400);
        }

        // Vérification supplémentaire pour actions critiques
        if (in_array($actionDecision->action_type, ['PROPOSE_SUSPENSION'])) {
            // Double validation requise (peut être implémentée avec feature flag)
            if (!$request->has('confirm_critical')) {
                return response()->json([
                    'error' => 'Critical action requires explicit confirmation',
                    'requires_confirmation' => true,
                ], 400);
            }
        }

        $result = $this->executionService->execute($actionDecision);

        if ($result['success']) {
            return response()->json([
                'message' => 'Action executed successfully',
                'result' => $result,
            ]);
        } else {
            return response()->json([
                'error' => 'Action execution failed',
                'result' => $result,
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des actions
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $targetType = $request->input('target_type');
        $targetId = $request->input('target_id');
        $status = $request->input('status');

        $query = AdminActionDecision::query()
            ->orderBy('created_at', 'desc');

        if ($targetType && $targetId) {
            $query->where('target_type', $targetType)
                ->where('target_id', $targetId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $actions = $query->limit($limit)->get();

        return response()->json([
            'history' => $actions,
            'total_count' => $actions->count(),
        ]);
    }

    /**
     * Obtenir les détails d'une action
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $actionDecision = AdminActionDecision::findOrFail($id);

        return response()->json([
            'action' => $actionDecision,
            'can_execute' => $actionDecision->canBeExecuted(),
        ]);
    }
}



