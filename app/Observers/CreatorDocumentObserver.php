<?php

namespace App\Observers;

use App\Models\CreatorDocument;
use App\Models\CreatorActivityLog;
use App\Services\CreatorNotificationService;
use App\Models\CreatorValidationChecklist;

class CreatorDocumentObserver
{
    protected CreatorNotificationService $notificationService;

    public function __construct(CreatorNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the CreatorDocument "created" event.
     */
    public function created(CreatorDocument $document): void
    {
        $creator = $document->creatorProfile;
        
        // Mettre à jour la checklist si le document correspond à un item
        $this->updateChecklistFromDocument($document);

        // Notifier les admins qu'il y a des documents à vérifier
        $pendingCount = $creator->documents()->where('is_verified', false)->count();
        if ($pendingCount > 0) {
            $this->notificationService->notifyAdminDocumentsToVerify($creator, $pendingCount);
        }
    }

    /**
     * Handle the CreatorDocument "updated" event.
     */
    public function updated(CreatorDocument $document): void
    {
        // Si le document vient d'être vérifié
        if ($document->isDirty('is_verified') && $document->is_verified) {
            $this->notificationService->notifyDocumentVerification(
                $document->creatorProfile,
                $document->document_type_label
            );

            // Mettre à jour la checklist
            $this->updateChecklistFromDocument($document);

            // Enregistrer l'activité
            CreatorActivityLog::log(
                $document->creator_profile_id,
                auth()->id() ?? $document->verified_by,
                'document_verified',
                "Document \"{$document->document_type_label}\" vérifié",
                ['is_verified' => false],
                ['is_verified' => true, 'document_type' => $document->document_type]
            );
        }
    }

    /**
     * Mettre à jour la checklist en fonction du document.
     */
    protected function updateChecklistFromDocument(CreatorDocument $document): void
    {
        $creator = $document->creatorProfile;
        
        // Mapping des types de documents vers les clés de checklist
        $documentTypeMapping = [
            'identity_card' => 'identity_document',
            'passport' => 'identity_document',
            'registration_certificate' => 'registration_certificate',
            'tax_id' => 'tax_id',
            'bank_statement' => 'bank_statement',
            'portfolio' => 'portfolio',
        ];

        $checklistKey = $documentTypeMapping[$document->document_type] ?? null;
        
        if ($checklistKey && $document->is_verified) {
            $checklistItem = CreatorValidationChecklist::where('creator_profile_id', $creator->id)
                ->where('item_key', $checklistKey)
                ->first();

            if ($checklistItem && !$checklistItem->is_completed) {
                $checklistItem->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                    'completed_by' => $document->verified_by,
                ]);

                // Notifier de la progression
                $this->notifyChecklistProgress($creator);
            }
        }
    }

    /**
     * Notifier de la progression de la checklist.
     */
    protected function notifyChecklistProgress($creator): void
    {
        $total = CreatorValidationChecklist::where('creator_profile_id', $creator->id)->count();
        $completed = CreatorValidationChecklist::where('creator_profile_id', $creator->id)
            ->where('is_completed', true)
            ->count();

        if ($total > 0) {
            $this->notificationService->notifyChecklistProgress($creator, $completed, $total);
        }
    }
}

