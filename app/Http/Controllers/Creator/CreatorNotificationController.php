<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreatorNotificationController extends Controller
{
    /**
     * Afficher la liste des notifications du créateur.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $query = Notification::where('user_id', $user->id);
        
        // Filtre par type (toutes ou non lues)
        if ($request->get('filter') === 'unread') {
            $query->where('is_read', false);
        }
        
        $notifications = $query->latest()
            ->paginate(20);
        
        // Compter les non lues
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
        
        return view('creator.notifications.index', compact('notifications', 'unreadCount'));
    }
    
    /**
     * Marquer une notification comme lue.
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        // Vérifier que la notification appartient au créateur connecté
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette notification.');
        }
        
        $notification->markAsRead();
        
        return redirect()->back()
            ->with('success', 'Notification marquée comme lue.');
    }
    
    /**
     * Marquer toutes les notifications comme lues.
     */
    public function markAllAsRead(): RedirectResponse
    {
        $user = Auth::user();
        
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        return redirect()->back()
            ->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}


