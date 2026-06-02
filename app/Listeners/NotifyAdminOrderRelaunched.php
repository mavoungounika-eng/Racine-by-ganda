<?php

namespace App\Listeners;

use App\Events\OrderRelaunched;
use App\Models\User;
use App\Notifications\OrderRelaunchedNotification;
use Illuminate\Support\Facades\Log;

class NotifyAdminOrderRelaunched
{
    public function handle(OrderRelaunched $event): void
    {
        $order = $event->order;

        Log::info('[ORDER] Commande relancée après restauration', [
            'order_id'   => $order->id,
            'user_id'    => $order->user_id,
            'total'      => $order->total_amount,
        ]);

        $admins = User::where('role', 'admin')
            ->orWhere('role', 'super_admin')
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new OrderRelaunchedNotification($order));
        }
    }
}
