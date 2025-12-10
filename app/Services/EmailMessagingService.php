<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Mail\NewMessageMail;
use App\Mail\MessageReplyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailMessagingService
{
    /**
     * Envoyer une notification email pour un nouveau message.
     */
    public function sendNewMessageNotification(Message $message, Conversation $conversation, User $recipient): void
    {
        // Vérifier si l'utilisateur a activé les notifications email
        if (!$recipient->email_notifications_enabled) {
            return;
        }

        // Obtenir l'email de messagerie de l'utilisateur
        $email = $recipient->messaging_email;
        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new NewMessageMail($message, $conversation, $recipient));
            
            Log::info('New message email sent', [
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new message email', [
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer un email directement depuis la messagerie.
     */
    public function sendEmailFromMessaging(
        Conversation $conversation,
        User $sender,
        string $subject,
        string $content,
        array $attachments = []
    ): bool {
        // Vérifier si l'expéditeur a activé l'envoi d'emails
        if (!$sender->email_messaging_enabled) {
            throw new \Exception('L\'envoi d\'emails depuis la messagerie n\'est pas activé pour votre compte.');
        }

        // Obtenir l'email de messagerie de l'expéditeur
        $senderEmail = $sender->messaging_email;
        if (!$senderEmail) {
            throw new \Exception('Aucune adresse email professionnelle configurée.');
        }

        // Obtenir le destinataire
        $otherParticipant = $conversation->participants()
            ->where('user_id', '!=', $sender->id)
            ->first();

        if (!$otherParticipant) {
            throw new \Exception('Destinataire non trouvé.');
        }

        $recipient = $otherParticipant->user;
        $recipientEmail = $recipient->preferred_email;

        try {
            $mail = new MessageReplyMail($conversation, $sender, $subject, $content, $attachments);
            Mail::to($recipientEmail)->send($mail);
            
            Log::info('Email sent from messaging', [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'sender_email' => $senderEmail,
                'recipient_email' => $recipientEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email from messaging', [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si un utilisateur peut envoyer des emails depuis la messagerie.
     */
    public function canSendEmail(User $user): bool
    {
        return $user->email_messaging_enabled 
            && $user->hasVerifiedProfessionalEmail();
    }
}

