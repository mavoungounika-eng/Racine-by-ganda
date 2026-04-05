<?php

namespace Tests\Feature\Pos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuthJsonResponseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario 1 - Token absent sur route POS (web-prefixed)
     */
    public function test_pos_route_returns_401_json_when_token_is_missing(): void
    {
        $response = $this->withHeaders(['Accept' => '*/*'])->get('/pos/sessions/current');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'error',
                'message',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'UNAUTHENTICATED');

        $this->assertStringContainsString('Token op', (string) $response->json('message'));
    }

    /**
     * Scenario 2 - Token absent sur route API POS
     */
    public function test_api_pos_route_returns_structured_401_json_when_token_is_missing(): void
    {
        $response = $this->withHeaders(['Accept' => '*/*'])->get('/api/pos/auth/operator/me');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'data',
                'error' => ['code', 'message', 'details'],
                'meta' => ['request_id', 'timestamp'],
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED')
            ->assertJsonPath('error.message', 'Missing bearer token');
    }

    /**
     * Scenario 3 - Route web NON affectee
     */
    public function test_web_routes_are_not_affected(): void
    {
        $loginResponse = $this->get('/login');
        $this->assertTrue(in_array($loginResponse->status(), [200, 302]), "HTTP status {$loginResponse->status()} received instead of 200/302 for /login.");
        $loginResponse->assertDontSee('UNAUTHENTICATED');

        $webProtectedResponse = $this->withHeaders(['Accept' => '*/*'])->get('/admin/dashboard');

        if ($webProtectedResponse->status() === 302) {
            $webProtectedResponse->assertRedirect();
            $webProtectedResponse->assertDontSee('UNAUTHENTICATED');
        } else {
            $this->assertNotEquals(401, $webProtectedResponse->status());
        }
    }
}