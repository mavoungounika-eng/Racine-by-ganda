<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Contrôleur pour le dashboard client
 * 
 * Gère l'affichage du dashboard client (/compte)
 * avec statistiques, commandes récentes et points de fidélité
 */
class ClientAccountController extends Controller
{
    use HandlesAuthRedirect;

    /**
     * Affiche le dashboard client
     * 
     * Sécurité : redirige vers le dashboard approprié si l'utilisateur n'est pas un client
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Charger la relation roleRelation si nécessaire
        $user->loadMissing('roleRelation');

        // SÉCURITÉ : Vérifier que l'utilisateur est bien un client
        $roleSlug = method_exists($user, 'getRoleSlug') 
            ? $user->getRoleSlug() 
            : null;
        
        if ($roleSlug !== 'client') {
            // Rediriger vers le dashboard approprié selon le rôle
            return redirect($this->getRedirectPath($user));
        }

        // Statistiques du client
        $stats = [
            'my_orders_total' => Order::where('user_id', $user->id)->count(),
            'my_orders_pending' => Order::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'processing', 'paid'])
                ->count(),
            'my_orders_completed' => Order::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'delivered'])
                ->count(),
            'total_spent' => Order::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
        ];

        // 5 dernières commandes
        $my_orders = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->latest()
            ->take(5)
            ->get();

        // Points de fidélité (si le modèle existe)
        $loyalty = null;
        if (class_exists(\App\Models\LoyaltyPoint::class)) {
            $loyalty = \App\Models\LoyaltyPoint::firstOrCreate(
                ['user_id' => $user->id],
                ['points' => 0, 'tier' => 'bronze']
            );
        }

        // Compteur de notifications non lues
        $unreadCount = 0;
        if (class_exists(\App\Models\Notification::class)) {
            $unreadCount = \App\Models\Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        }

        return view('account.dashboard', compact('stats', 'my_orders', 'loyalty', 'user', 'unreadCount'));
    }
}

