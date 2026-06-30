<?php

namespace App\Http\Controllers\Client;

use App\Events\OrderRelaunched;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientOrderController extends Controller
{
    /**
     * Affiche le formulaire de modification des quantités avant relance.
     * Accessible uniquement pour les commandes en statut 'restored'.
     */
    public function showRelaunchForm(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->status === 'restored', 403);

        $order->load(['items.product']);

        return view('client.orders.relaunch', compact('order'));
    }

    /**
     * Relance une commande restaurée : met à jour les quantités,
     * recalcule le total et remet en statut pending.
     */
    public function relaunch(Order $order, Request $request): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->status === 'restored', 403);

        $validated = $request->validate([
            'quantities'   => 'required|array',
            'quantities.*' => 'required|integer|min:1|max:100',
        ]);

        DB::transaction(function () use ($order, $validated) {
            foreach ($validated['quantities'] as $itemId => $quantity) {
                $item = $order->items()->where('id', $itemId)->first();
                if ($item) {
                    $item->update(['quantity' => $quantity]);
                }
            }

            $order->recalculateTotal();
            $order->update([
                'status'         => 'pending',
                'original_total' => $order->total_amount,
            ]);
        });

        event(new OrderRelaunched($order->fresh()));

        return redirect()->route('profile.orders.show', $order)
            ->with('success', 'Commande #' . $order->id . ' relancée avec succès.');
    }
}
