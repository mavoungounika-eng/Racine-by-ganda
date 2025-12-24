<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée lors d'une connexion depuis un nouvel appareil
 * 
 * Alerte l'utilisateur pour détecter les accès non autorisés
 */
class NewDeviceLogin extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $ipAddress,
        private string $userAgent,
        private string $loginTime
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
            ->subject('🔐 Nouvelle connexion détectée sur votre compte')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Une connexion à votre compte a été détectée depuis un nouvel appareil.')
            ->line('**Détails de la connexion :**')
            ->line('📍 Adresse IP : ' . $this->ipAddress)
            ->line('💻 Navigateur : ' . $this->extractBrowser($this->userAgent))
            ->line('🕐 Date et heure : ' . $this->loginTime)
            ->line('Si cette connexion provient de vous, vous pouvez ignorer ce message.')
            ->line('**Si ce n\'était pas vous**, votre compte pourrait être compromis.')
            ->action('Sécuriser mon compte', route('profile.security'))
            ->line('Nous vous recommandons de changer immédiatement votre mot de passe et d\'activer l\'authentification à deux facteurs.')
            ->salutation('L\'équipe RACINE');
    }

    /**
     * Extrait le nom du navigateur depuis le user agent
     */
    private function extractBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) {
            return 'Google Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Mozilla Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'Microsoft Edge';
        }
        
        return 'Navigateur inconnu';
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'login_time' => $this->loginTime,
        ];
    }
}
