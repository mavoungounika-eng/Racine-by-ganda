<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Obtenir les notifications de l'utilisateur connecté
     */
    public function index(Request $request): \Illuminate\View\View|JsonResponse
    {
        $user = Auth::user();
        $limit = $request->input('limit', 20);
        $unreadOnly = $request->boolean('unread_only', false);
        $filter = $request->input('filter', 'all'); // all, unread, read

        // Si requête AJAX, retourner JSON
        if ($request->ajax() || $request->wantsJson()) {
            if ($unreadOnly || $filter === 'unread') {
                $notifications = $this->notificationService->getUnreadForUser($user, $limit);
            } else {
                $notifications = $this->notificationService->getForUser($user, $limit);
            }

            return response()->json([
                'status' => 'success',
                'notifications' => $notifications,
                'unread_count' => $this->notificationService->countUnread($user),
            ]);
        }

        // Sinon, retourner la vue
        $query = \App\Models\Notification::where('user_id', $user->id);

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();
        $unreadCount = $this->notificationService->countUnread($user);

        return view('profile.notifications', compact('notifications', 'unreadCount', 'filter'));
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function count(): JsonResponse
    {
        $count = $this->notificationService->countUnread(Auth::user());

        return response()->json([
            'status' => 'success',
            'count' => $count,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = \App\Models\Notification::find($id);

        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Notification non trouvée'], 404);
        }

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Non autorisé'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marquée comme lue',
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(Auth::user());

        return response()->json([
            'status' => 'success',
            'message' => "{$count} notification(s) marquée(s) comme lue(s)",
            'count' => $count,
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(int $id): JsonResponse
    {
        $notification = \App\Models\Notification::find($id);

        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Notification non trouvée'], 404);
        }

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Non autorisé'], 403);
        }

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function deleteRead(): JsonResponse
    {
        $count = $this->notificationService->deleteReadForUser(Auth::user());

        return response()->json([
            'status' => 'success',
            'message' => "{$count} notification(s) supprimée(s)",
            'count' => $count,
        ]);
    }
}

