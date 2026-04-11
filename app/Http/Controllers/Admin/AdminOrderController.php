<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contrôleur pour la gestion des commandes (admin)
 * 
 * Gère l'affichage, la recherche et la mise à jour des commandes
 */
class AdminOrderController extends AdminController
{
    /**
     * Afficher la liste des commandes avec recherche et filtres.
     * 
     * @param Request $request Requête avec paramètres de recherche/filtres
     * @return View Vue avec liste paginée des commandes
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);
        $query = Order::with(['user', 'items.product'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Afficher le détail d'une commande.
     * 
     * @param Order $order La commande à afficher
     * @return View Vue avec détails de la commande
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load('items.product', 'user', 'address', 'payments');
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Mettre à jour le statut d'une commande.
     * 
     * @param Request $request Requête avec le nouveau statut
     * @param Order $order La commande à mettre à jour
     * @return RedirectResponse Redirection avec message de succès
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        $request->validate([
            'status' => 'required|in:pending,paid,shipped,completed,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Statut de la commande mis à jour.');
    }

    /**
     * Afficher le QR Code d'une commande
     */
    public function showQr(Order $order): View
    {
        $this->authorize('view', $order);
        $url = route('admin.orders.show', $order);
        return view('admin.orders.qrcode', compact('order', 'url'));
    }

    /**
     * Afficher le formulaire de scan
     */
    public function scanForm(): View
    {
        $this->authorize('viewAny', Order::class);
        return view('admin.orders.scan');
    }

    /**
     * Traiter le code scanné
     */
    public function scanHandle(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Order::class);
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->code);

        // Essayer de trouver par qr_token
        $order = Order::where('qr_token', $code)->first();

        // Si pas trouvé et que c'est un nombre, essayer par ID
        if (!$order && is_numeric($code)) {
            $order = Order::find($code);
        }

        if (!$order) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['code' => 'Aucune commande trouvée pour ce code.']);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Commande trouvée et chargée.');
    }
}
