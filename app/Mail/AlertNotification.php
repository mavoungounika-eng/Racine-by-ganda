<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public array $alert;

    public function __construct(array $alert)
    {
        $this->alert = $alert;
    }

    public function envelope(): Envelope
    {
        $severity = strtoupper($this->alert['severity']);
        
        return new Envelope(
            subject: "[{$severity}] {$this->alert['title']} - RACINE Monitoring",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-notification',
            with: ['alert' => $this->alert],
        );
    }
}
