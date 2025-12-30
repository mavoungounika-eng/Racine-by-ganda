<?php

namespace App\Observers;

use App\Models\CreatorProfile;
use App\Models\CreatorActivityLog;
use App\Models\CreatorValidationStep;
use App\Services\CreatorNotificationService;
use App\Services\CreatorScoringService;
use App\Models\CreatorValidationChecklist;

class CreatorProfileObserver
{
    protected CreatorNotificationService $notificationService;
    protected CreatorScoringService $scoringService;

    public function __construct(
        CreatorNotificationService $notificationService,
        CreatorScoringService $scoringService
    ) {
        $this->notificationService = $notificationService;
        $this->scoringService = $scoringService;
    }

    /**
     * Handle the CreatorProfile "created" event.
     */
    public function created(CreatorProfile $creatorProfile): void
    {
        // Initialiser la checklist de validation
        CreatorValidationChecklist::initializeForCreator($creatorProfile->id);

        // Initialiser les étapes de validation
        CreatorValidationStep::initializeForCreator($creatorProfile->id);

        // Notifier les admins d'un nouveau créateur
        $this->notificationService->notifyAdminNewCreator($creatorProfile);

        // Notifier le créateur
        $this->notificationService->notifyStatusChange(
            $creatorProfile,
            'none',
            $creatorProfile->status ?? 'pending'
        );

        // Enregistrer l'activité
        CreatorActivityLog::log(
            $creatorProfile->id,
            $creatorProfile->user_id,
            'other',
            'Profil créateur créé'
        );
    }

    /**
     * Handle the CreatorProfile "updated" event.
     */
    public function updated(CreatorProfile $creatorProfile): void
    {
        // Vérifier si le statut a changé
        if ($creatorProfile->isDirty('status')) {
            $oldStatus = $creatorProfile->getOriginal('status');
            $newStatus = $creatorProfile->status;
            
            $this->notificationService->notifyStatusChange(
                $creatorProfile,
                $oldStatus,
                $newStatus
            );

            // Enregistrer l'activité
            CreatorActivityLog::log(
                $creatorProfile->id,
                auth()->id() ?? $creatorProfile->user_id,
                'status_changed',
                "Statut changé de {$oldStatus} à {$newStatus}",
                ['status' => $oldStatus],
                ['status' => $newStatus]
            );
        }

        // Vérifier si la vérification a changé
        if ($creatorProfile->isDirty('is_verified')) {
            $isVerified = $creatorProfile->is_verified;
            
            $this->notificationService->notifyVerification(
                $creatorProfile,
                $isVerified
            );

            // Enregistrer l'activité
            CreatorActivityLog::log(
                $creatorProfile->id,
                auth()->id() ?? $creatorProfile->user_id,
                $isVerified ? 'verified' : 'unverified',
                $isVerified ? 'Compte vérifié' : 'Vérification retirée',
                ['is_verified' => !$isVerified],
                ['is_verified' => $isVerified]
            );

            // Mettre à jour les scores
            $this->scoringService->updateScores($creatorProfile);
        }

        // Mettre à jour les scores si d'autres champs importants ont changé
        if ($creatorProfile->isDirty(['bio', 'logo_path', 'banner_path', 'location', 'website'])) {
            $this->scoringService->updateScores($creatorProfile);
        }
    }

    /**
     * Handle the CreatorProfile "deleted" event.
     */
    public function deleted(CreatorProfile $creatorProfile): void
    {
        // Nettoyer les données associées si nécessaire
    }
}

