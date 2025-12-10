<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'site_name' => Cache::get('settings.site_name', config('app.name')),
            'site_email' => Cache::get('settings.site_email', config('mail.from.address')),
            'site_phone' => Cache::get('settings.site_phone', ''),
            'site_address' => Cache::get('settings.site_address', ''),
            
            // Réseaux sociaux
            'social_facebook' => Cache::get('settings.social_facebook', ''),
            'social_instagram' => Cache::get('settings.social_instagram', ''),
            'social_twitter' => Cache::get('settings.social_twitter', ''),
            'social_whatsapp' => Cache::get('settings.social_whatsapp', ''),
            
            // Marketplace
            'commission_rate' => Cache::get('settings.commission_rate', 15.00),
            'shipping_fee' => Cache::get('settings.shipping_fee', 2000),
            'currency' => Cache::get('settings.currency', 'FCFA'),
            'low_stock_threshold' => Cache::get('settings.low_stock_threshold', 10),
            
            // Paiement
            'stripe_mode' => Cache::get('settings.stripe_mode', 'test'),
            'payments_enabled' => Cache::get('settings.payments_enabled', true),
            
            // Système
            'maintenance_mode' => app()->isDownForMaintenance(),
            'registrations_enabled' => Cache::get('settings.registrations_enabled', true),
            'maintenance_message' => Cache::get('settings.maintenance_message', ''),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // Informations générales
            'site_name' => 'nullable|string|max:255',
            'site_email' => 'nullable|email',
            'site_phone' => 'nullable|string|max:50',
            'site_address' => 'nullable|string|max:255',
            
            // Réseaux sociaux
            'social_facebook' => 'nullable|url',
            'social_instagram' => 'nullable|url',
            'social_twitter' => 'nullable|url',
            'social_whatsapp' => 'nullable|string|max:50',
            
            // Marketplace
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'shipping_fee' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:FCFA,EUR,USD',
            'low_stock_threshold' => 'nullable|integer|min:1',
            
            // Paiement
            'stripe_mode' => 'nullable|string|in:test,live',
            'payments_enabled' => 'nullable|boolean',
            
            // Système
            'registrations_enabled' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:500',
        ]);

        // Convertir les checkboxes en booléens
        $validated['payments_enabled'] = $request->has('payments_enabled');
        $validated['registrations_enabled'] = $request->has('registrations_enabled');

        // Stocker tous les paramètres dans le cache (1 an)
        foreach ($validated as $key => $value) {
            Cache::put("settings.{$key}", $value, now()->addYear());
        }

        return redirect()->back()->with('success', 'Paramètres mis à jour avec succès !');
    }
}
