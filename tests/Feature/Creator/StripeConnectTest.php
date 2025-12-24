<?php

namespace Tests\Feature\Creator;

use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Services\Payments\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Tests\TestCase;

class StripeConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock StripeConnectService
        $this->instance(
            StripeConnectService::class,
            Mockery::mock(StripeConnectService::class, function ($mock) {
                // Actions par défaut
            })
        );
    }

    #[Test]
    public function it_redirects_to_stripe_onboarding_url()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Configurer le mock
        $service = Mockery::mock(StripeConnectService::class);
        $this->app->instance(StripeConnectService::class, $service);

        $service->shouldReceive('createAccount')->once()->andReturn(
            new CreatorStripeAccount([
                'creator_profile_id' => $profile->id,
                'stripe_account_id' => 'acct_test',
                'onboarding_status' => 'pending',
            ])
        );
        $service->shouldReceive('createOnboardingLink')->once()->andReturn('https://stripe.com/onboarding/test');

        $response = $this->get(route('creator.settings.stripe.connect'));

        $response->assertRedirect('https://stripe.com/onboarding/test');
    }

    #[Test]
    public function it_syncs_status_on_return()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'active',
            'is_active' => true,
        ]);

        $stripeAccount = CreatorStripeAccount::create([
            'creator_profile_id' => $profile->id,
            'stripe_account_id' => 'acct_test',
            'onboarding_status' => 'pending',
        ]);

        $this->actingAs($user);

        // Configurer le mock
        $service = Mockery::mock(StripeConnectService::class);
        $this->app->instance(StripeConnectService::class, $service);
        
        $service->shouldReceive('syncAccountStatus')->once()->with('acct_test');

        $response = $this->get(route('creator.settings.stripe.return'));

        $response->assertRedirect(route('creator.settings.payment'));
        $response->assertSessionHas('success');
    }
}
