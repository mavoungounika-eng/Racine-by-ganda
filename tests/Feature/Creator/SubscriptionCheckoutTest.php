<?php

namespace Tests\Feature\Creator;

use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\CreatorPlan;
use App\Services\CreatorSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_displays_available_plans()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'active',
            'is_active' => true,
        ]);

        CreatorPlan::create([
            'code' => 'basic',
            'name' => 'Plan Basic',
            'price' => 10000,
            'is_active' => true,
        ]);

        CreatorPlan::create([
            'code' => 'premium',
            'name' => 'Plan Premium',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('creator.subscription.plans'));

        $response->assertStatus(200);
        $response->assertSee('Plan Basic');
        $response->assertSee('Plan Premium');
    }

    #[Test]
    public function it_redirects_to_stripe_checkout()
    {
        // Mock le service AVANT de créer l'utilisateur
        $service = Mockery::mock(CreatorSubscriptionService::class);
        $this->app->instance(CreatorSubscriptionService::class, $service);

        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'active',
            'is_active' => true,
        ]);

        $plan = CreatorPlan::create([
            'code' => 'premium',
            'name' => 'Plan Premium',
            'price' => 50000,
            'is_active' => true,
            'stripe_price_id' => 'price_test_123',
        ]);

        $service->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn('https://checkout.stripe.com/test');

        $this->actingAs($user);

        $response = $this->get(route('creator.subscription.checkout', $plan));

        $response->assertRedirect('https://checkout.stripe.com/test');
    }

    #[Test]
    public function it_handles_successful_checkout_return()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'active',
            'is_active' => true,
        ]);

        $plan = CreatorPlan::create([
            'code' => 'premium',
            'name' => 'Plan Premium',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('creator.subscription.checkout.success', [
            'plan' => $plan,
            'session_id' => 'cs_test_123',
        ]));

        $response->assertRedirect(route('creator.dashboard'));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function it_handles_checkout_cancellation()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'active',
            'is_active' => true,
        ]);

        $plan = CreatorPlan::create([
            'code' => 'premium',
            'name' => 'Plan Premium',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('creator.subscription.checkout.cancel', $plan));

        $response->assertRedirect(route('creator.subscription.plans'));
        $response->assertSessionHas('info');
    }
}
