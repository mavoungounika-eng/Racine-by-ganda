<?php

namespace App\Notifications;

use App\Models\CreatorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification d'alerte de risque pour créateur
 *
 * Envoyée à l'administrateur quand RiskDetectionService détecte un créateur à risque critique.
 * T-05: Implémentation de l'envoi d'email suite à détection de risque financier.
 */
class CreatorRiskAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected CreatorProfile $creator;
    protected array $riskData;

    /**
     * Create a new notification instance.
     */
    public function __construct(CreatorProfile $creator, array $riskData)
    {
        $this->creator = $creator;
        $this->riskData = $riskData;
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $riskLevel = $this->riskData['risk_level'] ?? 'unknown';
        $reason = $this->riskData['risk_reason'] ?? 'Raison inconnue';
        $action = $this->riskData['suggested_action'] ?? 'Réviser manuellement';

        return (new MailMessage)
            ->subject("⚠️ Alerte Risque Créateur : {$this->creator->brand_name} ({$riskLevel})")
            ->greeting("Alerte de Risque Créateur")
            ->line("Le créateur **{$this->creator->brand_name}** a été détecté comme présentant un risque **{$riskLevel}**.")
            ->line("")
            ->line("**Raison de l'alerte :**")
            ->line($reason)
            ->line("")
            ->line("**Action suggérée :**")
            ->line($action)
            ->line("")
            ->line("Détails du créateur :")
            ->line("- **Nom** : {$this->creator->brand_name}")
            ->line("- **User ID** : {$this->creator->user_id}")
            ->line("- **Risque actuel** : {$riskLevel}")
            ->line("- **Statut** : {$this->creator->status}")
            ->action("Voir le Profil du Créateur", route('admin.creators.show', $this->creator->id))
            ->line("Consultez l'admin pour plus de détails et prendre les mesures nécessaires.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'creator_id' => $this->creator->id,
            'creator_name' => $this->creator->brand_name,
            'risk_level' => $this->riskData['risk_level'] ?? 'unknown',
            'risk_reason' => $this->riskData['risk_reason'] ?? '',
            'suggested_action' => $this->riskData['suggested_action'] ?? '',
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
