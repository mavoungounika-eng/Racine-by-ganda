<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification pour tentatives de connexion suspectes
 * 
 * PHASE 2 SÉCURITÉ : Alertes email pour comptes sensibles
 * Déclenchée après 3 échecs sur compte admin/super_admin
 */
class SuspiciousLoginAttempt extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $email,
        public string $ip,
        public int $attempts,
        public ?string $userAgent = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Tentative de connexion suspecte détectée')
            ->greeting('Alerte Sécurité')
            ->line("Des tentatives de connexion suspectes ont été détectées sur un compte administrateur.")
            ->line('')
            ->line("**Détails :**")
            ->line("• Email ciblé : {$this->email}")
            ->line("• Adresse IP : {$this->ip}")
            ->line("• Nombre de tentatives : {$this->attempts}")
            ->line("• User-Agent : " . ($this->userAgent ?? 'Non disponible'))
            ->line("• Horodatage : " . now()->format('d/m/Y H:i:s'))
            ->line('')
            ->line('Si vous n\'êtes pas à l\'origine de ces tentatives, veuillez prendre les mesures appropriées.')
            ->action('Voir les logs de sécurité', url('/admin/security/logs'))
            ->line('')
            ->line('Cette alerte est générée automatiquement par le système de sécurité RACINE BY GANDA.')
            ->salutation('Cordialement, L\'équipe Sécurité');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $this->email,
            'ip' => $this->ip,
            'attempts' => $this->attempts,
            'user_agent' => $this->userAgent,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
