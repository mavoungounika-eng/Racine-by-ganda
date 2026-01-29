<?php

namespace Tests\Feature\Creator;

use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Services\CreatorKycService;
use App\Notifications\KycCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KycAutomationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_detects_kyc_completion()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'pending',
            'is_active' => false,
        ]);

        $stripeAccount = CreatorStripeAccount::create([
            'creator_profile_id' => $profile->id,
            'stripe_account_id' => 'acct_test_123',
            'onboarding_status' => 'pending',
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'details_submitted' => false,
        ]);

        $kycService = new CreatorKycService();

        // Test : payouts_enabled passe de false à true
        $wasPayoutsEnabledBefore = false;
        $stripeAccount->update([
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);

        $this->assertTrue($kycService->hasKycJustCompleted($stripeAccount, $wasPayoutsEnabledBefore));
    }

    #[Test]
    public function it_does_not_detect_kyc_completion_if_already_enabled()
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
            'stripe_account_id' => 'acct_test_123',
            'onboarding_status' => 'complete',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);

        $kycService = new CreatorKycService();

        // Test : payouts_enabled était déjà true
        $wasPayoutsEnabledBefore = true;

        $this->assertFalse($kycService->hasKycJustCompleted($stripeAccount, $wasPayoutsEnabledBefore));
    }

    #[Test]
    public function it_sends_notification_when_kyc_is_completed()
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'pending',
            'is_active' => false,
        ]);

        $stripeAccount = CreatorStripeAccount::create([
            'creator_profile_id' => $profile->id,
            'stripe_account_id' => 'acct_test_123',
            'onboarding_status' => 'complete',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);

        $kycService = new CreatorKycService();
        $kycService->activatePayouts($stripeAccount);

        Notification::assertSentTo($user, KycCompletedNotification::class);
    }

    #[Test]
    public function it_activates_creator_profile_when_kyc_is_completed()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'status' => 'pending',
            'is_active' => false,
        ]);

        $stripeAccount = CreatorStripeAccount::create([
            'creator_profile_id' => $profile->id,
            'stripe_account_id' => 'acct_test_123',
            'onboarding_status' => 'complete',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);

        $kycService = new CreatorKycService();
        $kycService->activatePayouts($stripeAccount);

        $profile->refresh();

        $this->assertEquals('active', $profile->status);
        $this->assertTrue($profile->is_active);
    }

    #[Test]
    public function it_checks_kyc_status_correctly()
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
            'stripe_account_id' => 'acct_test_123',
            'onboarding_status' => 'complete',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);

        $kycService = new CreatorKycService();
        $status = $kycService->checkKycStatus($profile);

        $this->assertEquals('complete', $status['status']);
        $this->assertTrue($status['can_receive_payouts']);
    }
}
