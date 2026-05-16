<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
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
     * Endpoint JSON paginé pour la liste des commandes (vanilla JS).
     */
    public function dataOrders(Request $request): JsonResponse
    {
        $query = Order::with(['user:id,name,email'])->orderBy('created_at', 'desc');
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->get('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->get('date_fin'));
        }
        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    /**
     * Bulk — marquer comme complétées.
     */
    public function bulkComplete(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = Order::whereIn('id', $ids)->where('status', '!=', 'cancelled')->update(['status' => 'completed']);
        return response()->json(['success' => true, 'message' => $count.' commande(s) marquée(s) comme complète(s)']);
    }

    /**
     * Bulk — annuler.
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = Order::whereIn('id', $ids)->whereNotIn('status', ['completed', 'cancelled'])->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => $count.' commande(s) annulée(s)']);
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
