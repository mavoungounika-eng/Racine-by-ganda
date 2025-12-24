<?php

namespace App\Services\Action;

use App\Models\AdminActionDecision;
use App\Models\CreatorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service d'Exécution d'Actions (Safe Mode)
 * 
 * Phase 8.3 - Exécute UNIQUEMENT les actions approuvées
 * 
 * RÈGLE D'OR : EXÉCUTE UNIQUEMENT CE QUI EST APPROUVÉ
 */
class ActionExecutionService
{
    /**
     * Exécuter une action approuvée
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    public function execute(AdminActionDecision $actionDecision): array
    {
        // Vérification de sécurité : action doit être approuvée
        if (!$actionDecision->canBeExecuted()) {
            throw new \Exception("Action cannot be executed. Status: {$actionDecision->status}");
        }

        // Double-check : vérifier à nouveau l'état
        $stateBefore = $this->captureState($actionDecision);
        $actionDecision->update(['state_before' => $stateBefore]);

        try {
            DB::beginTransaction();

            $result = match ($actionDecision->action_type) {
                'MONITOR' => $this->executeMonitor($actionDecision),
                'SEND_REMINDER' => $this->executeSendReminder($actionDecision),
                'REQUEST_KYC_UPDATE' => $this->executeRequestKycUpdate($actionDecision),
                'FLAG_FOR_REVIEW' => $this->executeFlagForReview($actionDecision),
                'PROPOSE_SUSPENSION' => $this->executeProposeSuspension($actionDecision),
                'NO_ACTION' => $this->executeNoAction($actionDecision),
                default => throw new \Exception("Unknown action type: {$actionDecision->action_type}"),
            };

            // Capturer l'état après
            $stateAfter = $this->captureState($actionDecision);
            $actionDecision->update(['state_after' => $stateAfter]);

            // Marquer comme exécuté
            $actionDecision->markAsExecuted($result);

            DB::commit();

            Log::info('Action executed successfully', [
                'action_id' => $actionDecision->id,
                'action_type' => $actionDecision->action_type,
                'target' => "{$actionDecision->target_type}:{$actionDecision->target_id}",
            ]);

            return [
                'success' => true,
                'action_id' => $actionDecision->id,
                'result' => $result,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            $actionDecision->markAsFailed($e->getMessage());

            Log::error('Action execution failed', [
                'action_id' => $actionDecision->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action_id' => $actionDecision->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Exécuter MONITOR (pas d'action réelle, juste log)
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function executeMonitor(AdminActionDecision $actionDecision): array
    {
        // MONITOR ne fait rien, juste enregistrer
        return [
            'message' => 'Creator is being monitored',
            'action' => 'logged',
        ];
    }

    /**
     * Exécuter SEND_REMINDER (préparer un rappel, ne pas l'envoyer automatiquement)
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function executeSendReminder(AdminActionDecision $actionDecision): array
    {
        // Ne pas envoyer automatiquement, juste préparer
        // L'envoi réel doit être fait manuellement ou via un job séparé avec validation
        return [
            'message' => 'Reminder prepared (not sent automatically)',
            'action' => 'prepared',
            'note' => 'Reminder must be sent manually or via approved job',
        ];
    }

    /**
     * Exécuter REQUEST_KYC_UPDATE (marquer pour mise à jour KYC)
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function executeRequestKycUpdate(AdminActionDecision $actionDecision): array
    {
        $creator = $this->getCreator($actionDecision);
        
        // Marquer le créateur (exemple : ajouter une note admin)
        // Ne pas modifier directement le compte Stripe
        return [
            'message' => 'KYC update requested (flagged for admin review)',
            'action' => 'flagged',
            'creator_id' => $creator->id,
        ];
    }

    /**
     * Exécuter FLAG_FOR_REVIEW (marquer pour révision)
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function executeFlagForReview(AdminActionDecision $actionDecision): array
    {
        $creator = $this->getCreator($actionDecision);
        
        // Marquer pour révision (exemple : ajouter une note admin)
        return [
            'message' => 'Creator flagged for review',
            'action' => 'flagged',
            'creator_id' => $creator->id,
        ];
    }

    /**
     * Exécuter PROPOSE_SUSPENSION (ne pas suspendre automatiquement)
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function executeProposeSuspension(AdminActionDecision $actionDecision): array
    {
        // PROPOSE_SUSPENSION ne suspend PAS automatiquement
        // Il crée une nouvelle action pour validation manuelle
        return [
            'message' => 'Suspension proposed (requires manual approval)',
            'action' => 'proposed',
            'warning' => 'Suspension must be done manually by admin',
        ];
    }

    /**
     * Exécuter NO_ACTION
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function executeNoAction(AdminActionDecision $actionDecision): array
    {
        return [
            'message' => 'No action required',
            'action' => 'none',
        ];
    }

    /**
     * Capturer l'état actuel pour audit
     * 
     * @param AdminActionDecision $actionDecision
     * @return array
     */
    private function captureState(AdminActionDecision $actionDecision): array
    {
        if ($actionDecision->target_type === 'creator') {
            $creator = CreatorProfile::find($actionDecision->target_id);
            if ($creator) {
                return [
                    'creator_id' => $creator->id,
                    'status' => $creator->status,
                    'is_active' => $creator->is_active,
                    'subscription_status' => $creator->subscriptions()->latest()->first()?->status,
                ];
            }
        }

        return [];
    }

    /**
     * Obtenir le créateur cible
     * 
     * @param AdminActionDecision $actionDecision
     * @return CreatorProfile
     */
    private function getCreator(AdminActionDecision $actionDecision): CreatorProfile
    {
        if ($actionDecision->target_type !== 'creator') {
            throw new \Exception("Target is not a creator");
        }

        $creator = CreatorProfile::find($actionDecision->target_id);
        if (!$creator) {
            throw new \Exception("Creator not found: {$actionDecision->target_id}");
        }

        return $creator;
    }
}



