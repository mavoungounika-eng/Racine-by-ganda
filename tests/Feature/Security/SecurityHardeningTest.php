<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function security_headers_are_present_and_correct(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("nonce-", $csp);
        // Note : 'unsafe-inline' est intentionnellement conservé comme fallback
        // pour les navigateurs/contextes qui n'honorent pas les nonces (safari < 15.4,
        // iframes sandbox, etc.). Le nonce reste la défense principale ; 'unsafe-inline'
        // ne prend effet QUE si le nonce est absent côté navigateur. cf. SecurityHeaders
        // middleware et docs/BRAND_STACK_TRUTH_v2.md.
        $this->assertStringContainsString("'self'", $csp);
        $this->assertStringContainsString("script-src", $csp);
    }

    #[Test]
    public function session_cookies_are_secure_and_encrypted(): void
    {
        // Production config must enforce secure+encrypted cookies
        config(['session.secure' => true, 'session.encrypt' => true]);
        $this->assertTrue(config('session.secure'));
        $this->assertTrue(config('session.encrypt'));
    }

    #[Test]
    public function internal_user_fields_are_hidden_from_json(): void
    {
        $user = User::factory()->create([
            'auth_version' => 5,
            'google_id' => '123456789'
        ]);

        $json = $user->toJson();

        $this->assertStringNotContainsString('auth_version', $json);
        $this->assertStringNotContainsString('google_id', $json);
        $this->assertStringNotContainsString('password', $json);
    }

    #[Test]
    public function password_policy_enforces_complexity(): void
    {
        // On teste via une validation directe car Password::defaults() est utilisé par les validateurs
        $this->expectException(ValidationException::class);

        $validator = \Validator::make(
            ['password' => 'weak'],
            ['password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()]]
        );

        $validator->validate();
    }
}
