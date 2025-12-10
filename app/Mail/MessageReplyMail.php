<?php

namespace App\Mail;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MessageReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public Conversation $conversation;
    public User $sender;
    public string $subject;
    public string $content;
    public array $attachments;

    /**
     * Create a new message instance.
     */
    public function __construct(Conversation $conversation, User $sender, string $subject, string $content, array $attachments = [])
    {
        $this->conversation = $conversation;
        $this->sender = $sender;
        $this->subject = $subject;
        $this->content = $content;
        $this->attachments = $attachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->subject ?: 'Réponse à votre conversation';
        
        if ($this->conversation->subject && !$this->subject) {
            $subject = 'Re: ' . $this->conversation->subject;
        }

        return new Envelope(
            subject: $subject,
            from: $this->sender->messaging_email ?? $this->sender->email,
            replyTo: $this->sender->messaging_email ?? $this->sender->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.messages.reply',
            with: [
                'conversation' => $this->conversation,
                'sender' => $this->sender,
                'content' => $this->content,
                'conversationUrl' => route('messages.show', $this->conversation->id),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachments as $attachment) {
            if (is_string($attachment) && Storage::disk('public')->exists($attachment)) {
                $attachments[] = Attachment::fromStorageDisk('public', $attachment)
                    ->as(basename($attachment));
            } elseif (is_array($attachment) && isset($attachment['path'])) {
                $attachments[] = Attachment::fromStorageDisk(
                    $attachment['disk'] ?? 'public',
                    $attachment['path']
                )->as($attachment['name'] ?? basename($attachment['path']));
            }
        }

        return $attachments;
    }
}
