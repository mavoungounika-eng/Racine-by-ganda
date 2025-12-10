<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Affiche le profil de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
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
            $query->whereIn('status', ['pending', 'processing', 'paid']);
        } elseif ($statusFilter === 'terminees') {
            $query->whereIn('status', ['completed', 'delivered']);
        }
        // Si 'toutes' ou autre valeur, on affiche tout
        
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
        if ($user->isCreator()) {
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
        if ($user->isCreator() && $user->creatorProfile) {
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

        // TODO: Envoyer un email de vérification avec un token
        // Pour l'instant, on simule la vérification
        // Dans un vrai système, vous enverriez un email avec un lien de vérification

        $user->verifyProfessionalEmail();

        return back()->with('success', 'Email professionnel vérifié avec succès !');
    }
}

