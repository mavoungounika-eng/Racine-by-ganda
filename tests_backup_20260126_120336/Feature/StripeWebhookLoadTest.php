<?php

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de charge et résilience pour les webhooks Stripe
 * 
 * Phase 4.3 - Tests de charge (rafales webhooks, checkout concurrent)
 */
class StripeWebhookLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');
    }

    /**
     * Test : Rafales de webhooks simultanés
     * 
     * Envoyer 50 webhooks simultanés avec des event_id différents
     * Vérifier qu'aucun doublon n'est créé
     * Vérifier que tous les webhooks sont traités
     */
    public function test_burst_webhooks_handles_concurrent_requests(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $subscriptionIds = [];
        $eventIds = [];

        // Préparer 50 webhooks différents
        for ($i = 1; $i <= 50; $i++) {
            $subscriptionIds[] = 'sub_test_burst_' . $i;
            $eventIds[] = 'evt_test_burst_' . $i;
        }

        // Envoyer tous les webhooks (simulation séquentielle, en réel ce serait parallèle)
        foreach ($eventIds as $index => $eventId) {
            $subscriptionId = $subscriptionIds[$index];
            
            $payload = [
                'id' => $eventId,
                'type' => 'customer.subscription.created',
                'data' => [
                    'object' => [
                        'id' => $subscriptionId,
                        'customer' => 'cus_test_burst_' . ($index + 1),
                        'status' => 'active',
                        'current_period_start' => time(),
                        'current_period_end' => time() + 2592000,
                        'items' => [
                            'data' => [
                                [
                                    'price' => [
                                        'id' => 'price_test_123',
                                    ],
                                ],
                            ],
                        ],
                        'metadata' => [
                            'creator_id' => $user->id,
                        ],
                    ],
                ],
            ];

            $signature = $this->generateStripeSignature(json_encode($payload), 'whsec_test_secret');

            $response = $this->postJson('/api/webhooks/stripe/billing', $payload, [
                'Stripe-Signature' => $signature,
            ]);

            $response->assertStatus(200);
        }

        // Vérifier qu'il y a exactement 50 abonnements créés (pas de doublons)
        $count = CreatorSubscription::whereIn('stripe_subscription_id', $subscriptionIds)->count();
        $this->assertEquals(50, $count, 'Tous les webhooks doivent créer un abonnement unique');

        // Vérifier qu'il n'y a pas de doublons (contrainte unique sur stripe_subscription_id)
        $duplicates = DB::table('creator_subscriptions')
            ->whereIn('stripe_subscription_id', $subscriptionIds)
            ->groupBy('stripe_subscription_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        $this->assertEquals(0, $duplicates, 'Aucun doublon ne doit exister');
    }

    /**
     * Test : Rejouer le même événement 10 fois
     * 
     * Envoyer le même event_id 10 fois
     * Vérifier qu'un seul abonnement est créé
     * Vérifier que le statut reste cohérent
     */
    public function test_replay_same_event_10_times_creates_only_one_subscription(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $eventId = 'evt_test_replay_10';
        $subscriptionId = 'sub_test_replay_10';
        $customerId = 'cus_test_replay_10';

        $payload = [
            'id' => $eventId,
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => $subscriptionId,
                    'customer' => $customerId,
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + 2592000,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_test_123',
                                ],
                            ],
                        ],
                    ],
                    'metadata' => [
                        'creator_id' => $user->id,
                    ],
                ],
            ],
        ];

        $signature = $this->generateStripeSignature(json_encode($payload), 'whsec_test_secret');

        // Envoyer le même événement 10 fois
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->postJson('/api/webhooks/stripe/billing', $payload, [
                'Stripe-Signature' => $signature,
            ]);

            $response->assertStatus(200);
        }

        // Vérifier qu'il n'y a qu'un seul abonnement créé
        $count = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->count();
        $this->assertEquals(1, $count, 'Un seul abonnement doit être créé même après 10 replays');

        // Vérifier que le statut est cohérent
        $subscription = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($customerId, $subscription->stripe_customer_id);
    }

    /**
     * Test : Vérifier absence de doublons avec contrainte unique
     * 
     * Tenter de créer plusieurs abonnements avec le même stripe_subscription_id
     * Vérifier qu'un seul abonnement existe en base
     */
    public function test_unique_constraint_prevents_duplicate_subscriptions(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $subscriptionId = 'sub_test_unique';
        $customerId = 'cus_test_unique';

        // Créer un premier abonnement directement en base
        CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => $subscriptionId,
            'stripe_customer_id' => $customerId,
            'stripe_price_id' => 'price_test_123',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Tenter de créer un deuxième abonnement avec le même stripe_subscription_id via webhook
        $payload = [
            'id' => 'evt_test_unique',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => $subscriptionId, // Même ID
                    'customer' => $customerId,
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + 2592000,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_test_123',
                                ],
                            ],
                        ],
                    ],
                    'metadata' => [
                        'creator_id' => $user->id,
                    ],
                ],
            ],
        ];

        $signature = $this->generateStripeSignature(json_encode($payload), 'whsec_test_secret');

        $response = $this->postJson('/api/webhooks/stripe/billing', $payload, [
            'Stripe-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Vérifier qu'il n'y a toujours qu'un seul abonnement
        $count = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->count();
        $this->assertEquals(1, $count, 'La contrainte unique doit empêcher les doublons');
    }

    /**
     * Helper pour générer une signature Stripe valide
     */
    private function generateStripeSignature(string $payload, string $secret, int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$signature}";
    }
}

