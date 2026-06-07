<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminSettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Afficher la page settings avec navigation par onglets
     */
    public function index(string $tab = 'general'): View
    {
        // 🔒 SÉCURITÉ CRITIQUE : Seul Super Admin peut accéder aux paramètres système
        $this->authorize('access-system-config');

        // Charger les settings du groupe actuel
        $settings = $this->settingsService->getForView($tab);

        // Onglets disponibles
        $tabs = [
            'general' => ['icon' => 'fa-building', 'label' => 'Général', 'implemented' => true],
            'marketplace' => ['icon' => 'fa-store', 'label' => 'Marketplace', 'implemented' => false],
            'payments' => ['icon' => 'fa-credit-card', 'label' => 'Paiements', 'implemented' => false],
            'integrations' => ['icon' => 'fa-plug', 'label' => 'Intégrations', 'implemented' => false],
            'email' => ['icon' => 'fa-envelope', 'label' => 'Email & SMTP', 'implemented' => false],
            'security' => ['icon' => 'fa-shield-alt', 'label' => 'Sécurité', 'implemented' => false],
            'appearance' => ['icon' => 'fa-palette', 'label' => 'Apparence', 'implemented' => false],
            'advanced' => ['icon' => 'fa-cog', 'label' => 'Avancé', 'implemented' => false],
            'profile' => ['icon' => 'fa-user', 'label' => 'Mon Profil', 'implemented' => false],
        ];

        return view('admin.settings.index', [
            'currentTab' => $tab,
            'settings' => $settings,
            'tabs' => $tabs,
        ]);
    }

    /**
     * Mettre à jour les settings d'un onglet
     */
    public function update(Request $request, string $tab = 'general'): RedirectResponse
    {
        // 🔒 SÉCURITÉ CRITIQUE : Seul Super Admin peut modifier les paramètres système
        $this->authorize('access-system-config');

        // Validation selon l'onglet
        $validated = $this->validateForTab($request, $tab);

        // Sauvegarder via le service
        $this->settingsService->updateBatch($validated, $tab);

        return redirect()->route('admin.settings.index', $tab)
            ->with('success', 'Paramètres mis à jour avec succès !');
    }

    /**
     * Tester la connexion email
     */
    public function testEmail(): JsonResponse
    {
        $this->authorize('access-system-config');

        try {
            $user = auth()->user();

            Mail::raw('Ceci est un email de test depuis RACINE BY GANDA.', function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Test SMTP - RACINE BY GANDA');
            });

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé à ' . $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Test email failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Règles de validation selon l'onglet
     */
    private function validateForTab(Request $request, string $tab): array
    {
        return match ($tab) {
            'general' => $request->validate([
                // Informations entreprise
                'site_name' => 'nullable|string|max:255',
                'site_email' => 'nullable|email|max:255',
                'site_phone' => 'nullable|string|max:50',
                'site_address' => 'nullable|string|max:500',
                'site_country' => 'nullable|string|max:100',
                'site_timezone' => 'nullable|string|max:100',

                // Réseaux sociaux
                'social_facebook' => 'nullable|url|max:255',
                'social_instagram' => 'nullable|url|max:255',
                'social_twitter' => 'nullable|url|max:255',
                'social_whatsapp' => 'nullable|string|max:50',
                'social_tiktok' => 'nullable|url|max:255',
                'social_linkedin' => 'nullable|url|max:255',
            ]),

            'marketplace' => $request->validate([
                'commission_rate' => 'nullable|numeric|min:0|max:100',
                'shipping_fee' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|in:FCFA,EUR,USD',
                'low_stock_threshold' => 'nullable|integer|min:1',
            ]),

            'payments' => $request->validate([
                'stripe_mode' => 'nullable|string|in:test,live',
                'payments_enabled' => 'nullable|boolean',
            ]),

            'advanced' => $request->validate([
                'registrations_enabled' => 'nullable|boolean',
                'maintenance_message' => 'nullable|string|max:500',
            ]),

            // Autres onglets à implémenter dans les sprints suivants
            default => [],
        };
    }
}
