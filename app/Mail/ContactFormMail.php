<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The contact form data.
     */
    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $formData)
    {
        $this->data = $formData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Contact] {$this->data['subject']} - {$this->data['first_name']} {$this->data['last_name']}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'firstName' => $this->data['first_name'],
                'lastName' => $this->data['last_name'],
                'email' => $this->data['email'],
                'phone' => $this->data['phone'] ?? null,
                'subject' => $this->data['subject'],
                'message' => $this->data['message'],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
