<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\Attributes\Test;

use App\DTOs\Auth\AuthResult;
use App\Models\User;
use Tests\TestCase;

/**
 * AuthResult DTO Test
 * 
 * Tests the AuthResult DTO and its factory methods.
 */
class AuthResultTest extends TestCase
{
    #[Test]
    public function it_can_create_successful_result()
    {
        $user = User::factory()->make(['id' => 1]);
        $result = AuthResult::success($user, '/dashboard');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailed());
        $this->assertEquals($user, $result->user);
        $this->assertEquals('/dashboard', $result->redirectUrl);
        $this->assertEmpty($result->errors);
        $this->assertNull($result->challenge);
    }
    #[Test]
    public function it_can_create_failed_result()
    {
        $errors = ['email' => 'Invalid credentials'];
        $result = AuthResult::failed($errors);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailed());
        $this->assertNull($result->user);
        $this->assertNull($result->redirectUrl);
        $this->assertEquals($errors, $result->errors);
        $this->assertNull($result->challenge);
    }
    #[Test]
    public function it_can_create_captcha_required_result()
    {
        $result = AuthResult::captchaRequired();

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailed());
        $this->assertTrue($result->requiresCaptcha());
        $this->assertEquals('captcha', $result->challenge);
        $this->assertNotEmpty($result->errors);
    }
    #[Test]
    public function it_can_create_2fa_required_result()
    {
        $user = User::factory()->make(['id' => 1]);
        $result = AuthResult::twoFactorRequired($user, '/2fa/verify');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailed());
        $this->assertTrue($result->requires2FA());
        $this->assertEquals('2fa', $result->challenge);
        $this->assertEquals($user, $result->user);
        $this->assertEquals('/2fa/verify', $result->redirectUrl);
    }
    #[Test]
    public function it_can_get_first_error()
    {
        $errors = [
            'email' => 'Invalid email',
            'password' => 'Invalid password',
        ];
        $result = AuthResult::failed($errors);

        $this->assertEquals('Invalid email', $result->getFirstError());
    }
    #[Test]
    public function it_returns_null_when_no_errors()
    {
        $user = User::factory()->make(['id' => 1]);
        $result = AuthResult::success($user, '/dashboard');

        $this->assertNull($result->getFirstError());
    }
    #[Test]
    public function it_handles_array_error_values()
    {
        $errors = [
            'email' => ['First error', 'Second error'],
        ];
        $result = AuthResult::failed($errors);

        $this->assertEquals('First error', $result->getFirstError());
    }
    #[Test]
    public function it_can_convert_to_array()
    {
        $user = User::factory()->make(['id' => 1]);
        $result = AuthResult::success($user, '/dashboard', '2fa');

        $array = $result->toArray();

        $this->assertEquals([
            'success' => true,
            'user_id' => 1,
            'redirect_url' => '/dashboard',
            'errors' => [],
            'challenge' => '2fa',
            'metadata' => null,
        ], $array);
    }
    #[Test]
    public function it_includes_metadata_in_array()
    {
        $metadata = ['attempts_remaining' => 3];
        $result = AuthResult::failed(['email' => 'Invalid'], $metadata);

        $array = $result->toArray();

        $this->assertEquals($metadata, $array['metadata']);
    }
    #[Test]
    public function it_can_create_success_with_challenge()
    {
        $user = User::factory()->make(['id' => 1]);
        $result = AuthResult::success($user, '/dashboard', '2fa');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('2fa', $result->challenge);
        $this->assertTrue($result->requires2FA());
    }
    #[Test]
    public function it_distinguishes_between_captcha_and_2fa()
    {
        $captchaResult = AuthResult::requiresCaptcha();
        $user = User::factory()->make(['id' => 1]);
        $twoFAResult = AuthResult::requires2FA($user, '/2fa');

        $this->assertTrue($captchaResult->requiresCaptcha());
        $this->assertFalse($captchaResult->requires2FA());

        $this->assertTrue($twoFAResult->requires2FA());
        $this->assertFalse($twoFAResult->requiresCaptcha());
    }
}
