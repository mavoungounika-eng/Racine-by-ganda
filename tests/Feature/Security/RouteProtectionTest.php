<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorProfile;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        // Mock StripeConnectService pour éviter l'erreur de configuration
        $this->mock(\App\Services\Payments\StripeConnectService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });
    }

    private function seedRoles()
    {
        Role::create(['name' => 'Super Admin', 'slug' => 'super_admin', 'is_active' => true]);
        Role::create(['name' => 'Admin', 'slug' => 'admin', 'is_active' => true]);
        Role::create(['name' => 'Staff', 'slug' => 'staff', 'is_active' => true]);
        Role::create(['name' => 'Createur', 'slug' => 'createur', 'is_active' => true]);
        Role::create(['name' => 'Client', 'slug' => 'client', 'is_active' => true]);
    }

    /**
     * Test : Un Guest ne peut accéder à rien de protégé.
     */
    public function test_guest_is_blocked_on_protected_routes(): void
    {
        $protectedRoutes = [
            'admin.dashboard',
            'admin.kyc.index',
            'creator.dashboard',
            'messages.index',
            'profile.index',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect(route('login'));
        }
    }

    /**
     * Test : Un Client ne peut pas accéder aux routes Admin KYC.
     */
    public function test_client_cannot_access_admin_kyc(): void
    {
        $client = User::factory()->create(['role_id' => Role::where('slug', 'client')->first()->id]);
        
        $response = $this->actingAsWithContext($client)->get(route('admin.kyc.index'));
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : Un Créateur ne peut pas accéder aux abonnements des autres.
     */
    public function test_creator_cannot_access_admin_subscriptions(): void
    {
        $creator = User::factory()->create(['role_id' => Role::where('slug', 'createur')->first()->id]);
        
        $response = $this->actingAsWithContext($creator)->get(route('admin.creator-subscriptions.index'));
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : Admin peut accéder au KYC (Policy viewAny).
     */
    public function test_admin_with_permission_can_access_kyc(): void
    {
        // On suppose que l'admin a les permissions par défaut via son rôle
        $role = Role::where('slug', 'admin')->first();
        $permission = Permission::create(['name' => 'View Users', 'slug' => 'view-users']);
        $role->permissions()->attach($permission);

        $admin = User::factory()->create([
            'role_id' => $role->id,
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        
        // Simuler la validation de session/context
        $response = $this->actingAsWithContext($admin)->get(route('admin.kyc.index'));
        
        // Succès : l'admin a maintenant la permission
        $response->assertStatus(200);
    }

    /**
     * Test : Les routes webhook legacy peuvent être coupées via configuration.
     * Vérifie qu'une fois désactivées, elles retournent 410 Gone.
     */
    public function test_legacy_checkout_routes_can_be_disabled(): void
    {
        config(['payments.legacy_webhooks_enabled' => false]);

        $legacyPaths = [
            '/webhooks/stripe',
            '/payment/card/webhook',
        ];

        foreach ($legacyPaths as $path) {
            $response = $this->post($path);
            $response->assertStatus(410);
        }
    }
}
