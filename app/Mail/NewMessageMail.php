<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public Message $message;
    public Conversation $conversation;
    public User $recipient;
    public User $sender;

    /**
     * Create a new message instance.
     */
    public function __construct(Message $message, Conversation $conversation, User $recipient)
    {
        $this->message = $message;
        $this->conversation = $conversation;
        $this->recipient = $recipient;
        $this->sender = $message->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Nouveau message de ' . $this->sender->name;
        
        if ($this->conversation->subject) {
            $subject .= ' - ' . $this->conversation->subject;
        }

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            replyTo: $this->sender->messaging_email ?? $this->sender->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.messages.new-message',
            with: [
                'message' => $this->message,
                'conversation' => $this->conversation,
                'sender' => $this->sender,
                'recipient' => $this->recipient,
                'conversationUrl' => route('messages.show', $this->conversation->id),
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
