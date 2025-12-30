<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Créer une notification pour un utilisateur
     */
    public function create(
        User|int $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?array $data = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'data' => $data,
        ]);
    }

    /**
     * Notification de succès
     */
    public function success(User|int $user, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->create($user, $title, $message, 'success', '✅', $actionUrl);
    }

    /**
     * Notification d'information
     */
    public function info(User|int $user, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->create($user, $title, $message, 'info', 'ℹ️', $actionUrl);
    }

    /**
     * Notification d'avertissement
     */
    public function warning(User|int $user, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->create($user, $title, $message, 'warning', '⚠️', $actionUrl);
    }

    /**
     * Notification de danger/erreur
     */
    public function danger(User|int $user, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->create($user, $title, $message, 'danger', '🚨', $actionUrl);
    }

    /**
     * Notification de commande
     */
    public function order(User|int $user, string $title, string $message, ?int $orderId = null): Notification
    {
        // Utiliser checkout.success comme route publique pour les notifications de commande
        // Si l'utilisateur est connecté, on peut utiliser profile.orders.show
        $actionUrl = $orderId ? route('checkout.success', ['order' => $orderId]) : null;
        return $this->create($user, $title, $message, 'order', '📦', $actionUrl, 'Voir la commande', ['order_id' => $orderId]);
    }

    /**
     * Notification de stock
     */
    public function stock(User|int $user, string $title, string $message, ?int $productId = null): Notification
    {
        $actionUrl = $productId ? route('admin.products.edit', $productId) : route('erp.stocks.index');
        return $this->create($user, $title, $message, 'stock', '📊', $actionUrl, 'Voir les stocks', ['product_id' => $productId]);
    }

    /**
     * Notification système
     */
    public function system(User|int $user, string $title, string $message): Notification
    {
        return $this->create($user, $title, $message, 'system', '⚙️');
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function broadcast(array $userIds, string $title, string $message, string $type = 'info'): Collection
    {
        $notifications = collect();
        
        foreach ($userIds as $userId) {
            $notifications->push($this->create($userId, $title, $message, $type));
        }
        
        return $notifications;
    }

    /**
     * Envoyer à tous les utilisateurs d'un rôle
     */
    public function broadcastToRole(string $role, string $title, string $message, string $type = 'info'): Collection
    {
        $userIds = User::where('role', $role)->pluck('id')->toArray();
        return $this->broadcast($userIds, $title, $message, $type);
    }

    /**
     * Envoyer à toute l'équipe (admin, staff, super_admin)
     */
    public function broadcastToTeam(string $title, string $message, string $type = 'info'): Collection
    {
        $userIds = User::whereIn('role', ['super_admin', 'admin', 'staff'])->pluck('id')->toArray();
        return $this->broadcast($userIds, $title, $message, $type);
    }

    /**
     * Obtenir les notifications d'un utilisateur
     */
    public function getForUser(User|int $user, int $limit = 20): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les notifications non lues
     */
    public function getUnreadForUser(User|int $user, int $limit = 10): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Compter les notifications non lues
     */
    public function countUnread(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)->unread()->count();
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(int $notificationId): bool
    {
        $notification = Notification::find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        
        return false;
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Supprimer les anciennes notifications (plus de 30 jours)
     */
    public function cleanOld(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Supprimer une notification
     */
    public function delete(int $notificationId): bool
    {
        return Notification::destroy($notificationId) > 0;
    }

    /**
     * Supprimer toutes les notifications lues d'un utilisateur
     */
    public function deleteReadForUser(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)->read()->delete();
    }
}

