<?php

namespace App\Notifications;

use App\Models\CreatorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected CreatorProfile $profile;

    /**
     * Create a new notification instance.
     */
    public function __construct(CreatorProfile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        return (new MailMessage)
            ->subject('✅ Vérification complète - Vous pouvez recevoir des paiements !')
            ->greeting("Bonjour {$notifiable->name} 👋")
            ->line("Excellente nouvelle ! Votre vérification d'identité est maintenant **complète**.")
            ->line("Vous pouvez désormais recevoir des paiements pour vos ventes sur RACINE BY GANDA.")
            ->line('**Prochaines étapes :**')
            ->line('• Ajoutez vos produits à votre boutique')
            ->line('• Configurez vos préférences de paiement')
            ->line('• Commencez à vendre et à recevoir vos revenus automatiquement')
            ->action('Accéder à mon tableau de bord', route('creator.dashboard'))
            ->line('Merci de faire partie de la communauté RACINE BY GANDA ! 🎉')
            ->salutation('L\'équipe RACINE BY GANDA');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kyc_completed',
            'title' => 'Vérification complète',
            'message' => 'Votre vérification d\'identité est complète. Vous pouvez maintenant recevoir des paiements.',
            'creator_profile_id' => $this->profile->id,
            'action_url' => route('creator.dashboard'),
        ];
    }
}
