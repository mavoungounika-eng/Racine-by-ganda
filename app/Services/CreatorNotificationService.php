<?php

namespace App\Services;

use App\Models\CreatorProfile;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CreatorNotificationService
{
    /**
     * Notifier un créateur de son changement de statut.
     */
    public function notifyStatusChange(CreatorProfile $creator, string $oldStatus, string $newStatus): void
    {
        $user = $creator->user;
        
        $messages = [
            'pending' => 'Votre demande de créateur est en attente de validation.',
            'active' => 'Félicitations ! Votre compte créateur a été validé et activé.',
            'suspended' => 'Votre compte créateur a été suspendu. Contactez le support pour plus d\'informations.',
        ];

        $message = $messages[$newStatus] ?? 'Le statut de votre compte créateur a changé.';

        $this->createNotification(
            $user,
            'Statut du compte créateur',
            $message,
            'creator_status_change',
            ['creator_id' => $creator->id, 'status' => $newStatus]
        );
    }

    /**
     * Notifier un créateur de sa vérification.
     */
    public function notifyVerification(CreatorProfile $creator, bool $isVerified): void
    {
        $user = $creator->user;
        
        $message = $isVerified
            ? 'Votre compte créateur a été vérifié par l\'administrateur.'
            : 'La vérification de votre compte créateur a été retirée.';

        $this->createNotification(
            $user,
            'Vérification du compte',
            $message,
            'creator_verification',
            ['creator_id' => $creator->id, 'is_verified' => $isVerified]
        );
    }

    /**
     * Notifier un créateur qu'un document a été vérifié.
     */
    public function notifyDocumentVerification(CreatorProfile $creator, string $documentType): void
    {
        $user = $creator->user;
        
        $this->createNotification(
            $user,
            'Document vérifié',
            "Votre document \"{$documentType}\" a été vérifié et approuvé.",
            'document_verified',
            ['creator_id' => $creator->id, 'document_type' => $documentType]
        );
    }

    /**
     * Notifier un créateur qu'un document est manquant ou rejeté.
     */
    public function notifyDocumentMissing(CreatorProfile $creator, string $documentType, ?string $reason = null): void
    {
        $user = $creator->user;
        
        $message = "Votre document \"{$documentType}\" est manquant ou doit être mis à jour.";
        if ($reason) {
            $message .= " Raison : {$reason}";
        }

        $this->createNotification(
            $user,
            'Document requis',
            $message,
            'document_missing',
            ['creator_id' => $creator->id, 'document_type' => $documentType, 'reason' => $reason]
        );
    }

    /**
     * Notifier un créateur de la progression de sa checklist.
     */
    public function notifyChecklistProgress(CreatorProfile $creator, int $completed, int $total): void
    {
        $user = $creator->user;
        $percentage = round(($completed / $total) * 100, 0);
        
        if ($percentage === 100) {
            $message = "Félicitations ! Votre dossier est complet. Il sera examiné par notre équipe sous peu.";
        } elseif ($percentage >= 75) {
            $message = "Votre dossier est presque complet ({$percentage}%). Il ne reste que quelques éléments à fournir.";
        } else {
            $message = "Votre dossier est complété à {$percentage}%. Veuillez compléter les éléments manquants.";
        }

        $this->createNotification(
            $user,
            'Progression du dossier',
            $message,
            'checklist_progress',
            ['creator_id' => $creator->id, 'completed' => $completed, 'total' => $total, 'percentage' => $percentage]
        );
    }

    /**
     * Notifier les admins d'un nouveau créateur en attente.
     */
    public function notifyAdminNewCreator(CreatorProfile $creator): void
    {
        $admins = User::whereHas('roleRelation', function ($query) {
            $query->where('slug', 'admin');
        })->get();

        foreach ($admins as $admin) {
            $this->createNotification(
                $admin,
                'Nouveau créateur en attente',
                "Un nouveau créateur \"{$creator->brand_name}\" est en attente de validation.",
                'admin_new_creator',
                ['creator_id' => $creator->id]
            );
        }
    }

    /**
     * Notifier les admins de documents à vérifier.
     */
    public function notifyAdminDocumentsToVerify(CreatorProfile $creator, int $count): void
    {
        $admins = User::whereHas('roleRelation', function ($query) {
            $query->where('slug', 'admin');
        })->get();

        foreach ($admins as $admin) {
            $this->createNotification(
                $admin,
                'Documents à vérifier',
                "Le créateur \"{$creator->brand_name}\" a {$count} document(s) en attente de vérification.",
                'admin_documents_to_verify',
                ['creator_id' => $creator->id, 'count' => $count]
            );
        }
    }

    /**
     * Créer une notification.
     */
    protected function createNotification(
        User $user,
        string $title,
        string $message,
        string $type,
        array $data = []
    ): void {
        try {
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

