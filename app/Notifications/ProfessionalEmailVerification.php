<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProfessionalEmailVerification extends Notification
{
    use Queueable;

    private string $token;
    private string $professionalEmail;

    public function __construct(string $token, string $professionalEmail)
    {
        $this->token = $token;
        $this->professionalEmail = $professionalEmail;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('profile.professional-email.confirm', [
            'token' => $this->token,
            'email' => $this->professionalEmail,
        ]);

        return (new MailMessage)
            ->subject('Vérification de votre email professionnel — RacineByGanda')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Cliquez sur le bouton ci-dessous pour vérifier votre email professionnel.')
            ->action('Vérifier mon email', $url)
            ->line('Ce lien expire dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé cette vérification, ignorez cet email.');
    }
}
