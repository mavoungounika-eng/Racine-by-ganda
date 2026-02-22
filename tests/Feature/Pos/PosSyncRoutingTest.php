<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;

class PosSyncRoutingTest extends TestCase
{
    public function test_register_endpoint_is_exposed_under_api_pos_prefix(): void
    {
        $response = $this->postJson('/api/pos/register', []);

        // Route exists: validation should fail, not 404.
        $response->assertStatus(422);
    }

    public function test_protected_possync_endpoint_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/pos/status');

        $response
            ->assertStatus(401)
            ->assertJson(['error' => 'Missing bearer token']);
    }
}
