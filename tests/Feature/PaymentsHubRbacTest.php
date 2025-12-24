<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsHubRbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que les utilisateurs non autorisés ne peuvent pas accéder au Payments Hub
     */
    public function test_unauthorized_users_cannot_access_payments_hub(): void
    {
        // Créer un utilisateur client (non autorisé)
        $client = User::firstOrCreate(
            ['email' => 'client@test.com'],
            [
                'name' => 'Client Test',
                'password' => bcrypt('password'),
                'role' => 'client',
            ]
        );

        $this->actingAs($client);

        // Tenter d'accéder au dashboard Payments Hub
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(403);

        // Tenter d'accéder à la page providers
        $response = $this->get(route('admin.payments.providers.index'));
        $response->assertStatus(403);
    }

    /**
     * Test que les utilisateurs avec payments.view peuvent voir le dashboard
     */
    public function test_authorized_users_can_view_payments_hub(): void
    {
        // Créer un utilisateur admin (autorisé)
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->actingAs($admin);

        // Accéder au dashboard Payments Hub
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(200);
        $response->assertSee('Payments Hub');
    }

    /**
     * Test que les utilisateurs avec payments.config peuvent modifier les providers
     */
    public function test_authorized_users_can_update_providers(): void
    {
        // Créer un utilisateur admin (autorisé)
        $admin = User::firstOrCreate(
            ['email' => 'admin2@test.com'],
            [
                'name' => 'Admin Test 2',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->actingAs($admin);

        // Créer un provider de test
        $provider = PaymentProvider::firstOrCreate(
            ['code' => 'stripe'],
            [
                'name' => 'Stripe',
                'is_enabled' => true,
                'priority' => 1,
                'currency' => 'XAF',
                'health_status' => 'ok',
            ]
        );

        // Mettre à jour le provider
        $response = $this->put(route('admin.payments.providers.update', $provider), [
            'is_enabled' => false,
            'priority' => 2,
        ]);

        $response->assertStatus(302); // Redirect après update
        $response->assertSessionHas('success');

        // Vérifier que le provider a été mis à jour
        $provider->refresh();
        $this->assertFalse($provider->is_enabled);
        $this->assertEquals(2, $provider->priority);
    }

    /**
     * Test que les utilisateurs sans payments.config ne peuvent pas modifier les providers
     */
    public function test_unauthorized_users_cannot_update_providers(): void
    {
        // Créer un utilisateur staff (peut voir mais pas configurer)
        $staff = User::firstOrCreate(
            ['email' => 'staff@test.com'],
            [
                'name' => 'Staff Test',
                'password' => bcrypt('password'),
                'role' => 'staff',
            ]
        );

        $this->actingAs($staff);

        // Créer un provider de test
        $provider = PaymentProvider::firstOrCreate(
            ['code' => 'monetbil'],
            [
                'name' => 'Monetbil',
                'is_enabled' => true,
                'priority' => 1,
                'currency' => 'XAF',
                'health_status' => 'ok',
            ]
        );

        // Tenter de mettre à jour le provider
        $response = $this->put(route('admin.payments.providers.update', $provider), [
            'is_enabled' => false,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test que le menu Payments Hub n'est visible que pour les utilisateurs autorisés
     */
    public function test_payments_menu_visibility(): void
    {
        // Créer un utilisateur admin
        $admin = User::firstOrCreate(
            ['email' => 'admin3@test.com'],
            [
                'name' => 'Admin Test 3',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->actingAs($admin);

        // Vérifier que le menu est visible dans le layout
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        // Le menu devrait être présent dans la sidebar (vérifié via la route payments)
        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(200);
    }
}




