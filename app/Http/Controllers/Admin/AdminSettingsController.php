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
            'marketplace' => ['icon' => 'fa-store', 'label' => 'Marketplace', 'implemented' => true],
            'payments' => ['icon' => 'fa-credit-card', 'label' => 'Paiements', 'implemented' => true],
            'integrations' => ['icon' => 'fa-plug', 'label' => 'Intégrations', 'implemented' => false],
            'email' => ['icon' => 'fa-envelope', 'label' => 'Email & SMTP', 'implemented' => true],
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
     * Tester la connexion Stripe
     */
    public function testStripe(): JsonResponse
    {
        $this->authorize('access-system-config');

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Tenter de récupérer le compte Stripe
            $account = \Stripe\Account::retrieve();

            return response()->json([
                'success' => true,
                'message' => 'Connexion Stripe réussie ! Compte: ' . $account->id,
            ]);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur d\'authentification Stripe: Clé API invalide',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Test Stripe failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur Stripe: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tester la connexion Monetbil
     */
    public function testMonetbil(): JsonResponse
    {
        $this->authorize('access-system-config');

        try {
            $serviceKey = config('services.monetbil.service_key');
            $baseUrl = config('services.monetbil.base_url');

            if (!$serviceKey || !$baseUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration Monetbil incomplète (.env)',
                ], 500);
            }

            // Ping simple vers l'API Monetbil
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($baseUrl . '/status', [
                'service_key' => $serviceKey,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion Monetbil réussie ! API accessible.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur Monetbil: HTTP ' . $response->status(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Test Monetbil failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur Monetbil: ' . $e->getMessage(),
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
                'commission_rate' => 'required|numeric|min:0|max:100',
                'shipping_fee' => 'required|integer|min:0',
                'currency' => 'required|string|in:FCFA,EUR,USD',
                'low_stock_threshold' => 'required|integer|min:1',
                'low_stock_critical' => 'required|integer|min:1',
                'max_variants_per_product' => 'required|integer|min:1|max:100',
                'order_auto_cancel_hours' => 'required|integer|min:0|max:168',
                'auto_reorder_enabled' => 'boolean',
                'free_shipping_threshold' => 'required|integer|min:0',
            ]),

            'payments' => $request->validate([
                'stripe_mode' => 'required|string|in:test,live',
                'stripe_currency' => 'required|string|in:EUR,USD,GBP',
                'payments_enabled' => 'boolean',
                'monetbil_auto_approve_threshold' => 'required|integer|min:0',
                'payment_max_attempts' => 'required|integer|min:1|max:10',
                'payment_retry_enabled' => 'boolean',
            ]),

            'email' => $request->validate([
                'mail_from_name' => 'required|string|max:100',
                'mail_from_address' => 'required|email',
                'admin_notification_email' => 'nullable|email',
                'admin_notification_enabled' => 'boolean',
                'mail_logo_url' => 'nullable|url',
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
