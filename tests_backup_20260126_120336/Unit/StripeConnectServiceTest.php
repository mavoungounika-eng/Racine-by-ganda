<?php

namespace Tests\Unit;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Payments\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitaires pour StripeConnectService
 * 
 * Phase 4.1 - Tests unitaires (ZÉRO mock Stripe, uniquement modèles et payloads simulés)
 */
class StripeConnectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StripeConnectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StripeConnectService::class);
    }

    /**
     * Test : canCreatorReceivePayments() retourne true si toutes les conditions sont remplies
     */
    public function test_canCreatorReceivePayments_returns_true_when_all_conditions_met(): void
    {
        // Créer un utilisateur créateur
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        // Créer un profil créateur actif
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        // Créer un compte Stripe Connect complet
        $stripeAccount = CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_1234567890',
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        // Créer un abonnement actif
        CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_test_1234567890',
            'stripe_customer_id' => 'cus_test_1234567890',
            'stripe_price_id' => 'price_test_1234567890',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Vérifier que canCreatorReceivePayments retourne true
        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertTrue($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si pas de compte Stripe Connect
     */
    public function test_canCreatorReceivePayments_returns_false_when_no_stripe_account(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        // Pas de compte Stripe Connect créé

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si charges_enabled === false
     */
    public function test_canCreatorReceivePayments_returns_false_when_charges_not_enabled(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => false, // ❌ Pas activé
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si payouts_enabled === false
     */
    public function test_canCreatorReceivePayments_returns_false_when_payouts_not_enabled(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => false, // ❌ Pas activé
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si onboarding_status !== 'complete'
     */
    public function test_canCreatorReceivePayments_returns_false_when_onboarding_incomplete(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'in_progress', // ❌ Pas complet
            'details_submitted' => true,
        ]);

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si créateur non actif
     */
    public function test_canCreatorReceivePayments_returns_false_when_creator_inactive(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => false, // ❌ Non actif
            'status' => 'active',
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si status !== 'active'
     */
    public function test_canCreatorReceivePayments_returns_false_when_status_not_active(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'status' => 'suspended', // ❌ Suspendu
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si pas d'abonnement actif
     */
    public function test_canCreatorReceivePayments_returns_false_when_no_active_subscription(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        // Pas d'abonnement créé

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() retourne false si abonnement non actif
     */
    public function test_canCreatorReceivePayments_returns_false_when_subscription_not_active(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_test_' . uniqid(),
            'stripe_customer_id' => 'cus_test_' . uniqid(),
            'stripe_price_id' => 'price_test_' . uniqid(),
            'status' => 'unpaid', // ❌ Non actif
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $result = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result);
    }

    /**
     * Test : canCreatorReceivePayments() vérifie toutes les conditions dans l'ordre
     */
    public function test_canCreatorReceivePayments_checks_all_conditions_in_order(): void
    {
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator-2',
            'is_active' => true,
            'status' => 'active',
        ]);

        // Test 1 : Pas de compte Stripe
        $result1 = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result1, 'Doit retourner false si pas de compte Stripe');

        // Test 2 : Compte sans charges_enabled
        CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_' . uniqid(),
            'account_type' => 'express',
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'onboarding_status' => 'in_progress',
            'details_submitted' => false,
        ]);
        $result2 = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result2, 'Doit retourner false si charges_enabled === false');

        // Test 3 : Compte avec charges mais sans payouts
        $stripeAccount = CreatorStripeAccount::where('creator_profile_id', $creatorProfile->id)->first();
        $stripeAccount->update(['charges_enabled' => true, 'payouts_enabled' => false]);
        $result3 = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result3, 'Doit retourner false si payouts_enabled === false');

        // Test 4 : Compte avec charges et payouts mais onboarding incomplet
        $stripeAccount->update(['payouts_enabled' => true, 'onboarding_status' => 'in_progress']);
        $result4 = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result4, 'Doit retourner false si onboarding_status !== complete');

        // Test 5 : Tout OK sauf abonnement
        $stripeAccount->update(['onboarding_status' => 'complete']);
        $result5 = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertFalse($result5, 'Doit retourner false si pas d\'abonnement actif');

        // Test 6 : Tout OK
        CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_test_' . uniqid(),
            'stripe_customer_id' => 'cus_test_' . uniqid(),
            'stripe_price_id' => 'price_test_' . uniqid(),
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
        $result6 = $this->service->canCreatorReceivePayments($creatorProfile);
        $this->assertTrue($result6, 'Doit retourner true si toutes les conditions sont remplies');
    }
}

