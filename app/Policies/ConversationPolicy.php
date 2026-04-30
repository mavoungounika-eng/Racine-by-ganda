<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Admin voit tout, autres voient seulement leurs conversations
     */
    public function view(User $user, Conversation $conversation): bool
    {
        // Admin/Staff avec permission globale
        if ($user->hasPermission('view-users')) {
            return true;
        }
        
        // Autres : doivent être participants
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Envoyer message : participant uniquement
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    /**
     * Archiver : participant ou admin
     */
    public function archive(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    /**
     * Éditer message : auteur OU admin (dans les 5 min pour auteur)
     */
    public function editMessage(User $user, Message $message): bool
    {
        // Admin via permission settings
        if ($user->hasPermission('manage-settings')) {
            return true;
        }
        
        // Auteur peut éditer dans les 5 minutes
        return $message->user_id === $user->id 
            && $message->created_at->gt(now()->subMinutes(5));
    }

    /**
     * Supprimer : auteur OU admin
     */
    public function deleteMessage(User $user, Message $message): bool
    {
        return $user->hasPermission('manage-settings')
            || $message->user_id === $user->id;
    }
}
