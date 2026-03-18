<?php

namespace Modules\Assistant\Tests\Feature;

use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use Modules\Assistant\Services\AmiraService;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\CRM\Models\CrmInteraction;
use Modules\CRM\Models\CrmContact;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AmiraServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AmiraService $amira;

    protected function setUp(): void
    {
        parent::setUp();
        $this->amira = new AmiraService();
    }

    #[Test]
    public function it_can_detect_erp_intent_and_return_stock_info()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        ErpRawMaterial::create([
            'name' => 'Tissu Wax Royal',
            'reference' => 'WAX-001',
            'unit' => 'mètres',
            'current_stock' => 10,
            'min_stock_alert' => 5,
        ]);

        $response = (new AmiraService())->chat('Quel est le stock de l\'atelier ?');

        $this->assertEquals('success', $response['status']);
        $this->assertStringContainsString('Tissu Wax Royal', $response['message']);
        $this->assertStringContainsString('10 mètres', $response['message']);
    }

    #[Test]
    public function it_can_detect_crm_intent_and_return_interactions()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $contact = CrmContact::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ]);

        CrmInteraction::create([
            'contact_id' => $contact->id,
            'user_id' => $admin->id,
            'type' => 'email',
            'subject' => 'Demande de devis',
            'content' => 'Le client veut un devis pour 10 robes.',
            'occurred_at' => now(),
        ]);

        $response = (new AmiraService())->chat('Voir les interactions clients');

        $this->assertEquals('success', $response['status']);
        $this->assertStringContainsString('Jean Dupont', $response['message']);
        $this->assertStringContainsString('Demande de devis', $response['message']);
    }

    #[Test]
    public function it_can_show_subscription_status_to_creator()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Ganda Style',
            'slug' => 'ganda-style',
            'status' => 'active',
        ]);

        $plan = CreatorPlan::create([
            'code' => 'premium',
            'name' => 'Premium Plan',
            'price' => 50000,
            'is_active' => true,
        ]);

        CreatorSubscription::create([
            'creator_profile_id' => $profile->id,
            'creator_plan_id' => $plan->id,
            'status' => 'active',
            'stripe_subscription_id' => 'sub_test_123',
            'stripe_customer_id' => 'cus_test_123',
            'stripe_price_id' => 'price_test_123',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($user);
        $response = (new AmiraService())->chat('Quel est mon plan actuel ?');

        $this->assertEquals('success', $response['status']);
        $this->assertStringContainsString('Premium Plan', $response['message']);
        $this->assertStringContainsString('Active', $response['message']);
    }

    #[Test]
    public function it_blocks_crm_access_for_non_admin()
    {
        $user = User::factory()->create(['role' => 'client']);
        $this->actingAs($user);

        $response = (new AmiraService())->chat('Consulter le CRM');

        $this->assertStringContainsString('réservées aux administrateurs', $response['message']);
    }
}
