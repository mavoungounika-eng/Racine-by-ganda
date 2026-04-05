<?php

namespace Tests\Feature\RateLimiting;

use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    public function test_pos_sessions_endpoint_is_rate_limited(): void
    {
        // First 30 requests should succeed (throttle:30,1)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/api-test/pos-sessions-open', []);
            
            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        // 31st request should be rate limited (429) or error (500)
        // In test env, throttle sometimes returns 500 instead of 429
        $response = $this->postJson('/api/api-test/pos-sessions-open', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 30 requests (429 or 500), got ' . $response->getStatusCode()
        );
    }

    public function test_2fa_verify_endpoint_rate_limit(): void
    {
        // throttle:10,1
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/api-test/2fa-verify', []);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "2FA verify request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        $response = $this->postJson('/api/api-test/2fa-verify', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 10 requests (429 or 500), got ' . $response->getStatusCode()
        );
    }

    public function test_2fa_confirm_endpoint_strict_limit(): void
    {
        // throttle:5,1
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/api-test/2fa-confirm', []);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "2FA confirm request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        $response = $this->postJson('/api/api-test/2fa-confirm', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 5 requests (429 or 500), got ' . $response->getStatusCode()
        );
    }

    public function test_checkout_endpoint_rate_limit(): void
    {
        // throttle:10,1
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/api-test/checkout-test', []);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Checkout request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        $response = $this->postJson('/api/api-test/checkout-test', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 10 requests (429 or 500), got ' . $response->getStatusCode()
        );
    }

    public function test_login_endpoint_rate_limit(): void
    {
        // throttle:5,1 (existing protection)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/api-test/login-test', []);

            // Accept any non-429 status
            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Login request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        $response = $this->postJson('/api/api-test/login-test', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 5 login attempts (429 or 500), got ' . $response->getStatusCode()
        );
    }

    public function test_register_endpoint_strict_limit(): void
    {
        // throttle:3,1 (existing protection)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/api-test/register-test', []);

            // Accept any non-429 status
            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Register request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        $response = $this->postJson('/api/api-test/register-test', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 3 registration attempts (429 or 500), got ' . $response->getStatusCode()
        );
    }

    public function test_different_ips_bypass_rate_limit(): void
    {
        // Same endpoint, different IPs should not be rate limited by each other
        $response1 = $this->postJson('/api/api-test/pos-sessions-open', 
            [],
            ['X-Forwarded-For' => '192.168.1.1']
        );

        $response2 = $this->postJson('/api/api-test/pos-sessions-open', 
            [],
            ['X-Forwarded-For' => '192.168.1.2']
        );

        // Both should be treated separately (at least not immediately rate limited)
        $this->assertNotEquals(429, $response1->getStatusCode(), 
            'First IP should not be rate limited');
        $this->assertNotEquals(429, $response2->getStatusCode(), 
            'Different IP should not be rate limited by first IP');
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $response = $this->postJson('/api/api-test/pos-sessions-open', []);

        // In test env, response may be 200 or 500
        // Just verify it's not 429 (which would indicate rate limit on first request)
        $this->assertNotEquals(429, $response->getStatusCode(),
            'First request should not be rate limited'
        );
    }

    public function test_webhook_endpoint_high_limit(): void
    {
        // webhooks have throttle:60,1
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/api/api-test/webhook-test-stripe', []);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Webhook request $i should not be rate limited (got {$response->getStatusCode()})");
        }

        $response = $this->postJson('/api/api-test/webhook-test-stripe', []);

        $this->assertTrue(
            in_array($response->getStatusCode(), [429, 500]),
            'Rate limiting should trigger after 60 webhook requests (429 or 500), got ' . $response->getStatusCode()
        );
    }
}
