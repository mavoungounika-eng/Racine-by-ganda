<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $alertType;
    public array $details;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $alertType, array $details = [])
    {
        $this->user = $user;
        $this->alertType = $alertType;
        $this->details = $details;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'password_changed' => 'Alerte sécurité : Mot de passe modifié',
            'login_new_device' => 'Alerte sécurité : Connexion depuis un nouvel appareil',
            '2fa_enabled' => 'Alerte sécurité : Double authentification activée',
            '2fa_disabled' => 'Alerte sécurité : Double authentification désactivée',
            'suspicious_activity' => 'Alerte sécurité : Activité suspecte détectée',
        ];

        return new Envelope(
            subject: $subjects[$this->alertType] ?? 'Alerte sécurité - RACINE BY GANDA',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security.alert',
            with: [
                'user' => $this->user,
                'alertType' => $this->alertType,
                'details' => $this->details,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
