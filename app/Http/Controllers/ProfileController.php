<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Affiche le profil de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();
        // Charge commandes et adresses en une seule passe — évite la double requête
        // avec orders() qui recharge les mêmes données pour le même utilisateur
        $orders = Order::where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'archived'])
            ->with(['items.product'])
            ->latest()
            ->paginate(10);
        $addresses = Address::where('user_id', $user->id)->get();
        return view('profile.index', compact('user', 'orders', 'addresses'));
    }

    /**
     * Affiche l'historique des commandes avec filtres
     * 
     * Filtres disponibles :
     * - ?status=en-cours → pending, processing, paid
     * - ?status=terminees → completed, delivered
     * - Par défaut → toutes les commandes
     */
    public function orders()
    {
        $user = Auth::user();
        
        // Récupérer le filtre de statut depuis la query string
        $statusFilter = request()->query('status', 'toutes');
        
        // Construire la requête de base
        $query = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->latest();
        
        // Appliquer le filtre selon le statut
        if ($statusFilter === 'en-cours') {
            $query->whereIn('status', ['pending', 'processing']);
        } elseif ($statusFilter === 'terminees') {
            $query->whereIn('status', ['completed', 'delivered']);
        } elseif ($statusFilter === 'annulees') {
            $query->where('status', 'cancelled');
        }
        // Si 'toutes' → exclure annulées et archivées
        if ($statusFilter === 'toutes') {
            $query->whereNotIn('status', ['cancelled', 'archived']);
        }
        // Si autre valeur non reconnue, on affiche tout
        
        // Pagination avec préservation des query strings
        $orders = $query->paginate(15)->withQueryString();
        
        return view('profile.orders', compact('orders', 'statusFilter'));
    }

    /**
     * Affiche le détail d'une commande
     * 
     * SÉCURITÉ : Vérifie que la commande appartient bien à l'utilisateur connecté
     */
    public function showOrder(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        // Charger les relations nécessaires
        $order->load(['items.product', 'address']);

        return view('profile.order-detail', compact('order'));
    }

    /**
     * Gestion des adresses
     */
    public function addresses()
    {
        $user = Auth::user();
        $addresses = Address::where('user_id', $user->id)->get();
        return view('profile.addresses', compact('addresses'));
    }

    /**
     * Créer une adresse
     */
    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        // Si c'est la première adresse ou si is_default est true, mettre à jour les autres
        if ($request->boolean('is_default')) {
            Address::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $validated['user_id'] = Auth::id();
        Address::create($validated);

        return redirect()->route('profile.addresses')
            ->with('success', 'Adresse ajoutée avec succès !');
    }

    /**
     * Supprimer une adresse
     */
    public function deleteAddress(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }
        $address->delete();
        return redirect()->route('profile.addresses')
            ->with('success', 'Adresse supprimée avec succès !');
    }

    /**
     * Affiche le formulaire de modification du profil
     */
    public function edit()
    {
        $user = Auth::user();
        $user->load('creatorProfile');
        
        // Déterminer le layout selon le rôle
        $roleSlug = $user->getRoleSlug();
        $view = 'profile.edit';
        
        // Pour les créateurs, charger le profil créateur
        $creatorProfile = null;
        if ($user->hasRole('createur')) {
            $creatorProfile = $user->creatorProfile;
        }
        
        return view($view, compact('user', 'creatorProfile', 'roleSlug'));
    }

    /**
     * Met à jour les informations du profil (unifié pour tous les rôles)
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $roleSlug = $user->getRoleSlug();

        // Validation de base pour tous les rôles
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'professional_email' => 'nullable|email|max:255|unique:users,professional_email,' . $user->id,
            'email_notifications_enabled' => 'nullable|boolean',
            'email_messaging_enabled' => 'nullable|boolean',
        ];

        // Champs supplémentaires selon le rôle
        if ($roleSlug === 'staff') {
            $rules['staff_role'] = 'nullable|string|max:100';
        }

        // Locale pour admin/staff
        if (in_array($roleSlug, ['super_admin', 'admin', 'staff'])) {
            $rules['locale'] = 'nullable|string|in:fr,en';
        }

        $validated = $request->validate($rules);

        // Mise à jour des champs de base
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'staff_role' => $validated['staff_role'] ?? $user->staff_role,
            'locale' => $validated['locale'] ?? $user->locale,
        ];

        // Gestion de l'email professionnel
        if (isset($validated['professional_email'])) {
            $updateData['professional_email'] = $validated['professional_email'];
            // Si l'email change, réinitialiser la vérification
            if ($validated['professional_email'] !== $user->professional_email) {
                $updateData['professional_email_verified'] = false;
                $updateData['professional_email_verified_at'] = null;
            }
        }

        // Préférences email
        if (isset($validated['email_notifications_enabled'])) {
            $updateData['email_notifications_enabled'] = $validated['email_notifications_enabled'];
        }
        if (isset($validated['email_messaging_enabled'])) {
            $updateData['email_messaging_enabled'] = $validated['email_messaging_enabled'];
        }

        $user->update($updateData);

        // Mise à jour du profil créateur si applicable
        if ($user->hasRole('createur') && $user->creatorProfile) {
            $creatorRules = [
                'brand_name' => 'required|string|max:255',
                'bio' => 'nullable|string|max:5000',
                'location' => 'nullable|string|max:255',
                'website' => 'nullable|url|max:255',
                'instagram_url' => 'nullable|url|max:255',
                'tiktok_url' => 'nullable|url|max:255',
                'facebook_url' => 'nullable|url|max:255',
                'type' => 'nullable|string|max:100',
                'legal_status' => 'nullable|string|max:100',
                'registration_number' => 'nullable|string|max:100',
            ];

            $creatorValidated = $request->validate($creatorRules);

            $user->creatorProfile->update($creatorValidated);
        }

        // Redirection selon le rôle
        $redirectRoute = match($roleSlug) {
            'super_admin', 'admin', 'staff' => 'admin.dashboard',
            'createur' => 'creator.dashboard',
            default => 'profile.index',
        };

        return redirect()->route($redirectRoute)
            ->with('success', 'Profil mis à jour avec succès !');
    }

    /**
     * Met à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // ✅ FINAL HARDENING - Révoquer trusted device lors du changement de mot de passe
        $twoFactorService = app(\App\Services\TwoFactorService::class);
        $twoFactorService->revokeTrustedDevice($user);
        
        // Supprimer le cookie trusted_device
        cookie()->queue(cookie()->forget('trusted_device'));
        
        // Logger l'événement de sécurité
        \Log::channel('security')->info('Password changed, trusted device revoked', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('profile.index')
            ->with('success', 'Mot de passe modifié avec succès !');
    }

    /**
     * Afficher les points de fidélité
     */
    public function loyalty()
    {
        $user = Auth::user();
        $loyaltyPoint = $user->loyaltyPoints;
        $transactions = $user->loyaltyTransactions()->latest()->paginate(20);
        
        return view('profile.loyalty', compact('loyaltyPoint', 'transactions'));
    }

    /**
     * Vérifier l'email professionnel (envoie un email de vérification)
     */
    public function verifyProfessionalEmail(Request $request)
    {
        $user = Auth::user();

        if (!$user->professional_email) {
            return back()->withErrors(['professional_email' => 'Aucun email professionnel configuré.']);
        }

        if ($user->professional_email_verified) {
            return back()->with('info', 'Cet email est déjà vérifié.');
        }

        $token = \Illuminate\Support\Str::random(64);

        $user->update(['professional_email_token' => hash('sha256', $token)]);

        $user->notify(new \App\Notifications\ProfessionalEmailVerification(
            $token,
            $user->professional_email
        ));

        return back()->with('success', 'Un email de vérification a été envoyé à ' . $user->professional_email . '.');
    }

    public function restoreItem(Order $order, OrderItem $item, Request $request): RedirectResponse
    {
        $this->authorize('view', $order);

        if ($order->status !== 'pending') {
            abort(403, 'Restauration impossible : commande non en attente.');
        }

        if ($item->order_id !== $order->id) {
            abort(404);
        }

        try {
            $item->restore();
        } catch (\App\Exceptions\InvalidOrderItemTransitionException $e) {
            return back()->with('error', 'Cet article ne peut pas être restauré (statut actuel : ' . $item->status . ').');
        }

        $order->recalculateTotal();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Article restauré.']);
        }

        return back()->with('success', 'Article restauré dans la commande.');
    }

    public function cancelItem(Order $order, OrderItem $item, Request $request)
    {
        $this->authorize('view', $order);

        if ($order->status !== 'pending') {
            abort(403, 'Impossible de modifier une commande déjà traitée.');
        }

        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $item->cancel();
        $order->recalculateTotal();

        if ($order->items()->active()->count() === 0) {
            $order->update(['status' => 'cancelled']);
        }

        $message = 'Article retiré de la commande.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function cancelOrder(Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        if ($order->status !== 'pending') {
            return back()->with('error', 'Seules les commandes en attente peuvent être annulées.');
        }

        $order->update(['status' => 'cancelled']);

        \Log::channel('security')->info('Order cancelled by client', [
            'order_id' => $order->id,
            'user_id'  => Auth::id(),
        ]);

        return redirect()->route('profile.orders')
            ->with('success', 'Commande #' . $order->id . ' annulée avec succès.');
    }

    public function updateOrderItemQuantity(Order $order, OrderItem $item, Request $request): RedirectResponse
    {
        $this->authorize('view', $order);

        if ($order->status !== 'pending') {
            return back()->with('error', 'Impossible de modifier une commande déjà traitée.');
        }

        $request->validate(['quantity' => 'required|integer|min:1|max:100']);

        $item->update(['quantity' => $request->integer('quantity')]);
        $order->update([
            'total_amount' => $order->items()->sum(DB::raw('price * quantity')),
        ]);

        return back()->with('success', 'Quantité mise à jour.');
    }

    public function requestReturn(Order $order, Request $request): RedirectResponse
    {
        $this->authorize('view', $order);

        if (!in_array($order->status, ['completed', 'delivered'])) {
            return back()->with('error', 'Seules les commandes livrées peuvent faire l\'objet d\'un retour.');
        }

        $request->validate(['reason' => 'required|string|min:20|max:1000']);

        $conversation = \App\Models\Conversation::create([
            'type'             => \App\Models\Conversation::TYPE_ORDER_THREAD,
            'subject'          => 'Retour produit — Commande #' . $order->id,
            'related_order_id' => $order->id,
            'created_by'       => Auth::id(),
            'last_message_at'  => now(),
        ]);

        $conversation->participants()->create(['user_id' => Auth::id(), 'role' => 'client']);

        \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => Auth::id(),
            'content'         => 'Demande de retour :\n\n' . $request->string('reason'),
            'type'            => 'text',
        ]);

        return redirect()->route('messages.show', $conversation)
            ->with('success', 'Votre demande de retour a été envoyée au support.');
    }

    public function confirmProfessionalEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('professional_email', $request->email)
            ->whereNotNull('professional_email_token')
            ->first();

        if (!$user || !hash_equals($user->professional_email_token, hash('sha256', $request->token))) {
            return redirect()->route('profile.edit')
                ->with('error', 'Lien de vérification invalide ou expiré.');
        }

        $user->update([
            'professional_email_verified'    => true,
            'professional_email_verified_at' => now(),
            'professional_email_token'       => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Email professionnel vérifié avec succès !');
    }
}

