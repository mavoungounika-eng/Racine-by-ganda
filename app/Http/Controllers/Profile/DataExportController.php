<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class DataExportController extends Controller
{
    /**
     * Exporte toutes les données personnelles de l'utilisateur (RGPD)
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $format = $request->input('format', 'json'); // json ou csv

        // Collecter toutes les données
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
            'orders' => Order::where('user_id', $user->id)
                ->with(['items.product', 'address'])
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'total_amount' => $order->total_amount,
                        'customer_name' => $order->customer_name,
                        'customer_email' => $order->customer_email,
                        'customer_phone' => $order->customer_phone,
                        'customer_address' => $order->customer_address,
                        'created_at' => $order->created_at->toIso8601String(),
                        'items' => $order->items->map(function ($item) {
                            return [
                                'product_title' => $item->product->title ?? null,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                            ];
                        }),
                    ];
                }),
            'addresses' => Address::where('user_id', $user->id)
                ->get()
                ->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'type' => $address->type,
                        'first_name' => $address->first_name,
                        'last_name' => $address->last_name,
                        'phone' => $address->phone,
                        'address_line_1' => $address->address_line_1,
                        'address_line_2' => $address->address_line_2,
                        'city' => $address->city,
                        'postal_code' => $address->postal_code,
                        'country' => $address->country,
                        'is_default' => $address->is_default,
                        'created_at' => $address->created_at->toIso8601String(),
                    ];
                }),
            'reviews' => Review::where('user_id', $user->id)
                ->with('product')
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'product_title' => $review->product->title ?? null,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'is_approved' => $review->is_approved,
                        'is_verified_purchase' => $review->is_verified_purchase,
                        'created_at' => $review->created_at->toIso8601String(),
                    ];
                }),
            'wishlist' => Wishlist::where('user_id', $user->id)
                ->with('product')
                ->get()
                ->map(function ($wishlist) {
                    return [
                        'id' => $wishlist->id,
                        'product_title' => $wishlist->product->title ?? null,
                        'added_at' => $wishlist->created_at->toIso8601String(),
                    ];
                }),
            'export_date' => now()->toIso8601String(),
        ];

        if ($format === 'csv') {
            return $this->exportAsCsv($data, $user);
        }

        // JSON par défaut
        $filename = "donnees-personnelles-{$user->id}-" . now()->format('Y-m-d') . '.json';
        
        return Response::json($data, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/json; charset=utf-8',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Exporte les données en CSV
     */
    protected function exportAsCsv(array $data, $user)
    {
        $filename = "donnees-personnelles-{$user->id}-" . now()->format('Y-m-d') . '.csv';
        
        $csv = fopen('php://temp', 'r+');
        
        // En-têtes
        fputcsv($csv, ['Type', 'Données']);
        
        // Utilisateur
        fputcsv($csv, ['Utilisateur', '']);
        fputcsv($csv, ['ID', $data['user']['id']]);
        fputcsv($csv, ['Nom', $data['user']['name']]);
        fputcsv($csv, ['Email', $data['user']['email']]);
        fputcsv($csv, ['Téléphone', $data['user']['phone'] ?? '']);
        fputcsv($csv, ['Date de création', $data['user']['created_at']]);
        fputcsv($csv, ['', '']);
        
        // Commandes
        fputcsv($csv, ['Commandes', '']);
        foreach ($data['orders'] as $order) {
            fputcsv($csv, ['Commande #' . $order['id'], '']);
            fputcsv($csv, ['  - Statut', $order['status']]);
            fputcsv($csv, ['  - Montant', $order['total_amount'] . ' FCFA']);
            fputcsv($csv, ['  - Date', $order['created_at']]);
        }
        fputcsv($csv, ['', '']);
        
        // Adresses
        fputcsv($csv, ['Adresses', '']);
        foreach ($data['addresses'] as $address) {
            fputcsv($csv, ['Adresse #' . $address['id'], '']);
            fputcsv($csv, ['  - Nom complet', $address['first_name'] . ' ' . $address['last_name']]);
            fputcsv($csv, ['  - Adresse', $address['address_line_1']]);
            fputcsv($csv, ['  - Ville', $address['city']]);
            fputcsv($csv, ['  - Pays', $address['country']]);
        }
        
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);
        
        return Response::make($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Affiche la page de suppression de compte
     */
    public function showDeleteAccount()
    {
        $user = Auth::user();
        
        // Statistiques pour confirmation
        $stats = [
            'orders_count' => Order::where('user_id', $user->id)->count(),
            'addresses_count' => Address::where('user_id', $user->id)->count(),
            'reviews_count' => Review::where('user_id', $user->id)->count(),
            'wishlist_count' => Wishlist::where('user_id', $user->id)->count(),
        ];
        
        return view('profile.delete-account', compact('stats'));
    }

    /**
     * Supprime le compte utilisateur (RGPD)
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'confirm' => 'required|accepted',
        ]);

        $user = Auth::user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Le mot de passe est incorrect.']);
        }

        // Vérifier la confirmation
        if ($request->confirm !== '1' && $request->confirm !== 'yes' && $request->confirm !== true) {
            return back()->withErrors(['confirm' => 'Vous devez confirmer la suppression de votre compte.']);
        }

        DB::beginTransaction();
        
        try {
            // Anonymiser les données (RGPD)
            $user->update([
                'name' => 'Utilisateur supprimé',
                'email' => 'deleted_' . $user->id . '_' . time() . '@deleted.local',
                'phone' => null,
            ]);

            // Supprimer les données personnelles
            Wishlist::where('user_id', $user->id)->delete();
            Review::where('user_id', $user->id)->delete();
            Address::where('user_id', $user->id)->delete();
            
            // Les commandes sont conservées pour historique mais anonymisées
            Order::where('user_id', $user->id)->update([
                'customer_name' => 'Client supprimé',
                'customer_email' => 'deleted_' . $user->id . '@deleted.local',
                'customer_phone' => null,
            ]);

            // Déconnecter l'utilisateur
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Supprimer le compte
            $user->delete();

            DB::commit();

            return redirect()->route('frontend.home')
                ->with('success', 'Votre compte a été supprimé avec succès. Toutes vos données personnelles ont été anonymisées conformément au RGPD.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Une erreur est survenue lors de la suppression de votre compte. Veuillez réessayer ou contacter le support.');
        }
    }
}
