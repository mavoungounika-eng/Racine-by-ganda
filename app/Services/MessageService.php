<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ConversationParticipant;
use App\Models\MessageAttachment;
use App\Services\NotificationService;
use App\Services\EmailMessagingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// use Intervention\Image\Facades\Image; // Optionnel - nécessite package intervention/image

class MessageService
{
    protected NotificationService $notificationService;
    protected EmailMessagingService $emailMessagingService;

    public function __construct(NotificationService $notificationService, EmailMessagingService $emailMessagingService)
    {
        $this->notificationService = $notificationService;
        $this->emailMessagingService = $emailMessagingService;
    }

    /**
     * Envoyer un message dans une conversation
     */
    public function sendMessage(int $conversationId, int $userId, string $content, array $attachments = []): Message
    {
        return DB::transaction(function () use ($conversationId, $userId, $content, $attachments) {
            // Vérifier que l'utilisateur est participant
            $participant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->first();

            if (!$participant) {
                throw new \Exception('Vous n\'êtes pas participant à cette conversation.');
            }

            // Déterminer le type de message
            $type = !empty($attachments) ? Message::TYPE_ATTACHMENT : Message::TYPE_TEXT;

            // Créer le message
            $message = Message::create([
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'content' => $content,
                'type' => $type,
            ]);

            // Traiter les pièces jointes
            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    $this->attachFile($message, $file);
                }
            }

            // Marquer comme lu par l'expéditeur
            $message->markAsReadBy($userId);
            $participant->markAsRead();

            // Mettre à jour la date du dernier message de la conversation
            $conversation = Conversation::find($conversationId);
            $conversation->updateLastMessageAt();

            // Incrémenter le compteur de non lus pour les autres participants
            $this->incrementUnreadForOthers($conversationId, $userId);

            // Envoyer des notifications aux autres participants
            $this->notifyParticipants($conversation, $message, $userId);

            // Envoyer des emails aux participants qui ont activé les notifications email
            $this->sendEmailNotifications($conversation, $message, $userId);

            Log::info('Message sent', [
                'message_id' => $message->id,
                'conversation_id' => $conversationId,
                'user_id' => $userId,
            ]);

            return $message->load(['user', 'attachments']);
        });
    }

    /**
     * Obtenir les messages d'une conversation
     */
    public function getMessages(int $conversationId, int $userId, ?int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        // Vérifier que l'utilisateur est participant
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return collect();
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();

        // Marquer comme lu
        $this->markConversationAsRead($conversationId, $userId);

        return $messages;
    }

    /**
     * Marquer une conversation comme lue
     */
    public function markConversationAsRead(int $conversationId, int $userId): void
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($participant) {
            $participant->markAsRead();

            // Marquer tous les messages comme lus
            Message::where('conversation_id', $conversationId)
                ->where('user_id', '!=', $userId)
                ->get()
                ->each(function ($message) use ($userId) {
                    $message->markAsReadBy($userId);
                });
        }
    }

    /**
     * Éditer un message
     */
    public function editMessage(int $messageId, int $userId, string $newContent): Message
    {
        $message = Message::findOrFail($messageId);

        // Vérifier que l'utilisateur est l'auteur
        if ($message->user_id !== $userId) {
            throw new \Exception('Vous ne pouvez éditer que vos propres messages.');
        }

        $message->update([
            'content' => $newContent,
        ]);

        $message->markAsEdited();

        return $message->fresh(['user', 'attachments']);
    }

    /**
     * Supprimer un message (soft delete)
     */
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = Message::findOrFail($messageId);

        // Vérifier que l'utilisateur est l'auteur ou admin
        $user = \App\Models\User::find($userId);
        $isAdmin = $user && in_array($user->getRoleSlug(), ['super_admin', 'admin']);

        if ($message->user_id !== $userId && !$isAdmin) {
            throw new \Exception('Vous ne pouvez supprimer que vos propres messages.');
        }

        return $message->delete();
    }

    /**
     * Attacher un fichier à un message
     */
    protected function attachFile(Message $message, UploadedFile $file): MessageAttachment
    {
        $path = $file->store('messages/attachments', 'public');
        $mimeType = $file->getMimeType();
        $fileType = str_starts_with($mimeType, 'image/') ? MessageAttachment::TYPE_IMAGE : MessageAttachment::TYPE_FILE;

        $attachment = MessageAttachment::create([
            'message_id' => $message->id,
            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $mimeType,
            'file_type' => $fileType,
        ]);

        // Si c'est une image, créer un thumbnail
        if ($fileType === MessageAttachment::TYPE_IMAGE) {
            $this->createThumbnail($attachment, $file);
        }

        return $attachment;
    }

    /**
     * Créer un thumbnail pour une image
     */
    protected function createThumbnail(MessageAttachment $attachment, UploadedFile $file): void
    {
        // Thumbnail désactivé pour l'instant (nécessite intervention/image)
        // Peut être activé plus tard si le package est installé
        try {
            // Détecter les dimensions si possible
            if (function_exists('getimagesize')) {
                $imageInfo = @getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $attachment->update([
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1],
                    ]);
                }
            }

            // TODO: Créer thumbnail si intervention/image est installé
            // $image = Image::make($file);
            // $thumbnail = $image->resize(300, 300, function ($constraint) {
            //     $constraint->aspectRatio();
            //     $constraint->upsize();
            // });
            // $thumbnailPath = 'messages/thumbnails/' . basename($attachment->file_path);
            // Storage::disk('public')->put($thumbnailPath, $thumbnail->encode());
            // $attachment->update(['thumbnail_path' => $thumbnailPath]);
        } catch (\Exception $e) {
            Log::warning('Failed to create thumbnail', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Incrémenter le compteur de non lus pour les autres participants
     */
    protected function incrementUnreadForOthers(int $conversationId, int $senderId): void
    {
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $senderId)
            ->where('notifications_enabled', true)
            ->get()
            ->each(function ($participant) {
                $participant->incrementUnread();
            });
    }

    /**
     * Notifier les participants d'un nouveau message
     */
    protected function notifyParticipants(Conversation $conversation, Message $message, int $senderId): void
    {
        $participants = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->where('notifications_enabled', true)
            ->where('is_archived', false)
            ->get();

        foreach ($participants as $participant) {
            $this->notificationService->info(
                $participant->user_id,
                'Nouveau message',
                $message->user->name . ' : ' . Str::limit($message->content, 50),
                route('messages.show', $conversation->id)
            );
        }
    }

    /**
     * Envoyer des notifications email aux participants
     */
    protected function sendEmailNotifications(Conversation $conversation, Message $message, int $senderId): void
    {
        $participants = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->where('notifications_enabled', true)
            ->where('is_archived', false)
            ->with('user')
            ->get();

        foreach ($participants as $participant) {
            if ($participant->user) {
                $this->emailMessagingService->sendNewMessageNotification(
                    $message,
                    $conversation,
                    $participant->user
                );
            }
        }
    }
}

