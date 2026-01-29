<?php

namespace Tests\Feature\RateLimiting;

use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    public function test_pos_sessions_endpoint_is_rate_limited(): void
    {
        // First 30 requests should succeed (throttle:30,1)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/pos/sessions/open', [
                'terminal_id' => 'TERM-001',
            ]);
            
            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Request $i should not be rate limited");
        }

        // 31st request should be rate limited
        $response = $this->postJson('/api/pos/sessions/open', [
            'terminal_id' => 'TERM-001',
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 30 requests');
    }

    public function test_2fa_verify_endpoint_rate_limit(): void
    {
        // throttle:10,1
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/2fa/verify', [
                'email' => 'test@example.com',
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "2FA verify request $i should not be rate limited");
        }

        $response = $this->postJson('/2fa/verify', [
            'email' => 'test@example.com',
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 10 requests for 2FA verify');
    }

    public function test_2fa_confirm_endpoint_strict_limit(): void
    {
        // throttle:5,1
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/2fa/confirm', [
                'code' => '123456',
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "2FA confirm request $i should not be rate limited");
        }

        $response = $this->postJson('/2fa/confirm', [
            'code' => '123456',
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 5 requests for 2FA confirm');
    }

    public function test_checkout_endpoint_rate_limit(): void
    {
        // throttle:10,1
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/checkout', [
                'cart_items' => [],
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Checkout request $i should not be rate limited");
        }

        $response = $this->postJson('/checkout', [
            'cart_items' => [],
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 10 requests');
    }

    public function test_login_endpoint_rate_limit(): void
    {
        // throttle:5,1 (existing protection)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ]);

            // Accept 401/422 but NOT 429
            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Login request $i should not be rate limited");
        }

        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 5 login attempts');
    }

    public function test_register_endpoint_strict_limit(): void
    {
        // throttle:3,1 (existing protection)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/register', [
                'name' => 'Test User ' . $i,
                'email' => "test{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            // Accept 422 but NOT 429
            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Register request $i should not be rate limited");
        }

        $response = $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 3 registration attempts');
    }

    public function test_different_ips_bypass_rate_limit(): void
    {
        // Same endpoint, different IPs should not be rate limited by each other
        $response1 = $this->postJson('/api/pos/sessions/open', 
            ['terminal_id' => 'TERM-001'],
            ['X-Forwarded-For' => '192.168.1.1']
        );

        $response2 = $this->postJson('/api/pos/sessions/open', 
            ['terminal_id' => 'TERM-002'],
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
        $response = $this->postJson('/api/pos/sessions/open', [
            'terminal_id' => 'TERM-001',
        ]);

        // Laravel includes rate limit headers
        $this->assertTrue(
            $response->headers->has('RateLimit-Limit') || 
            $response->headers->has('X-RateLimit-Limit'),
            'Response should include rate limit headers'
        );
    }

    public function test_webhook_endpoint_high_limit(): void
    {
        // webhooks have throttle:60,1
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/webhook/payment/stripe', [
                'id' => 'evt_test_' . $i,
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(), 
                "Webhook request $i should not be rate limited");
        }

        $response = $this->postJson('/webhook/payment/stripe', [
            'id' => 'evt_test_overflow',
        ]);

        $this->assertEquals(429, $response->getStatusCode(), 
            'Rate limiting should trigger after 60 webhook requests');
    }
}
