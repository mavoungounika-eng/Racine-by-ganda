<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationService
{
    /**
     * Créer une conversation directe entre deux utilisateurs
     */
    public function createDirectConversation(int $userId, int $recipientId, ?string $subject = null): Conversation
    {
        return DB::transaction(function () use ($userId, $recipientId, $subject) {
            // Vérifier si une conversation existe déjà
            $existing = $this->findDirectConversation($userId, $recipientId);
            if ($existing) {
                return $existing;
            }

            // Créer la conversation
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_DIRECT,
                'subject' => $subject ?? "Conversation entre utilisateurs",
                'created_by' => $userId,
            ]);

            // Ajouter les participants
            $conversation->participants()->create([
                'user_id' => $userId,
                'role' => ConversationParticipant::ROLE_SENDER,
            ]);

            $conversation->participants()->create([
                'user_id' => $recipientId,
                'role' => ConversationParticipant::ROLE_RECIPIENT,
            ]);

            Log::info('Direct conversation created', [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'recipient_id' => $recipientId,
            ]);

            return $conversation;
        });
    }

    /**
     * Créer un thread de discussion pour une commande
     */
    public function createOrderThread(Order $order, ?int $initiatorId = null): Conversation
    {
        return DB::transaction(function () use ($order, $initiatorId) {
            // Vérifier si un thread existe déjà
            $existing = Conversation::forOrder($order->id)->first();
            if ($existing) {
                return $existing;
            }

            $initiatorId = $initiatorId ?? $order->user_id ?? auth()->id();

            // Créer la conversation
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_ORDER_THREAD,
                'subject' => "Discussion - Commande #{$order->order_number}",
                'related_order_id' => $order->id,
                'created_by' => $initiatorId,
            ]);

            // Ajouter le client
            if ($order->user_id) {
                $conversation->participants()->create([
                    'user_id' => $order->user_id,
                    'role' => ConversationParticipant::ROLE_SENDER,
                ]);
            }

            // Ajouter les admins/staff
            $teamMembers = User::whereIn('role', ['super_admin', 'admin', 'staff'])
                ->pluck('id');
            
            foreach ($teamMembers as $teamId) {
                $conversation->participants()->create([
                    'user_id' => $teamId,
                    'role' => ConversationParticipant::ROLE_ADMIN,
                ]);
            }

            Log::info('Order thread created', [
                'conversation_id' => $conversation->id,
                'order_id' => $order->id,
            ]);

            return $conversation;
        });
    }

    /**
     * Créer un thread de discussion pour un produit
     */
    public function createProductThread(Product $product, int $userId): Conversation
    {
        return DB::transaction(function () use ($product, $userId) {
            // Vérifier si un thread existe déjà
            $existing = Conversation::forProduct($product->id)->first();
            if ($existing) {
                return $existing;
            }

            // Créer la conversation
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_PRODUCT_THREAD,
                'subject' => "Discussion - {$product->title}",
                'related_product_id' => $product->id,
                'created_by' => $userId,
            ]);

            // Ajouter l'utilisateur
            $conversation->participants()->create([
                'user_id' => $userId,
                'role' => ConversationParticipant::ROLE_SENDER,
            ]);

            // Ajouter le créateur du produit (si existe)
            if ($product->creator_id) {
                $conversation->participants()->create([
                    'user_id' => $product->creator_id,
                    'role' => ConversationParticipant::ROLE_RECIPIENT,
                ]);
            }

            // Ajouter les admins
            $admins = User::whereIn('role', ['super_admin', 'admin'])
                ->pluck('id');
            
            foreach ($admins as $adminId) {
                $conversation->participants()->create([
                    'user_id' => $adminId,
                    'role' => ConversationParticipant::ROLE_ADMIN,
                ]);
            }

            Log::info('Product thread created', [
                'conversation_id' => $conversation->id,
                'product_id' => $product->id,
            ]);

            return $conversation;
        });
    }

    /**
     * Trouver une conversation directe existante
     */
    public function findDirectConversation(int $userId, int $recipientId): ?Conversation
    {
        return Conversation::where('type', Conversation::TYPE_DIRECT)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereHas('participants', function ($q) use ($recipientId) {
                $q->where('user_id', $recipientId);
            })
            ->first();
    }

    /**
     * Obtenir les conversations d'un utilisateur
     */
    public function getConversationsForUser(int $userId, bool $archived = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = Conversation::whereHas('participants', function ($q) use ($userId, $archived) {
            $q->where('user_id', $userId);
            if ($archived) {
                $q->where('is_archived', true);
            } else {
                $q->where('is_archived', false);
            }
        })
        ->with(['lastMessage.user', 'participants.user'])
        ->orderBy('last_message_at', 'desc');

        return $query->get();
    }

    /**
     * Obtenir une conversation avec ses messages
     */
    public function getConversationWithMessages(int $conversationId, int $userId): ?Conversation
    {
        // Vérifier que l'utilisateur est participant
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return null;
        }

        return Conversation::with([
            'messages.user',
            'messages.attachments',
            'participants.user',
            'order',
            'product',
        ])->find($conversationId);
    }

    /**
     * Ajouter un participant à une conversation
     */
    public function addParticipant(int $conversationId, int $userId, string $role = ConversationParticipant::ROLE_PARTICIPANT): ConversationParticipant
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Vérifier si déjà participant
        $existing = $conversation->participants()->where('user_id', $userId)->first();
        if ($existing) {
            return $existing;
        }

        return $conversation->participants()->create([
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    /**
     * Archiver une conversation pour un utilisateur
     */
    public function archiveForUser(int $conversationId, int $userId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($participant) {
            $participant->update(['is_archived' => true]);
            return true;
        }

        return false;
    }

    /**
     * Désarchiver une conversation pour un utilisateur
     */
    public function unarchiveForUser(int $conversationId, int $userId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($participant) {
            $participant->update(['is_archived' => false]);
            return true;
        }

        return false;
    }

    /**
     * Obtenir le nombre de conversations non lues pour un utilisateur
     */
    public function getUnreadConversationsCount(int $userId): int
    {
        return ConversationParticipant::where('user_id', $userId)
            ->where('is_archived', false)
            ->where('unread_count', '>', 0)
            ->count();
    }
}

