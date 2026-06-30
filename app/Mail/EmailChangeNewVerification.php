<?php

namespace App\Mail;

use App\Models\EmailChange;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeNewVerification extends Mailable
{
    use Queueable, SerializesModels;

    public EmailChange $emailChange;
    public User $user;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(EmailChange $emailChange, User $user)
    {
        $this->emailChange = $emailChange;
        $this->user = $user;
        $this->verificationUrl = route('profile.email.verify-new', ['token' => $emailChange->new_email_token]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vérification de changement d\'email - Nouvel email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change.verify-new',
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
