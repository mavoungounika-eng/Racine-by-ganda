<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LegacyWebhookDeprecationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un appel legacy retourne les headers de dépréciation
     */
    public function test_legacy_endpoint_returns_deprecation_headers(): void
    {
        Log::spy();

        // Utiliser call() avec payload brut JSON pour éviter CSRF/session
        // Le middleware doit être appelé même si le controller retourne une erreur
        $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode([
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
        ]));

        $statusCode = $response->getStatusCode();
        $allHeaders = $response->headers->all();
        
        // Debug : afficher tous les headers pour comprendre
        // Le middleware devrait être appelé même pour les erreurs 400
        // Vérifier que la réponse n'est pas une erreur CSRF (419)
        $this->assertNotEquals(419, $statusCode, "CSRF token mismatch - status: {$statusCode}");

        // Vérifier les headers de dépréciation (même pour status 400)
        // Si le middleware n'est pas appelé, c'est un problème d'ordre d'exécution
        $this->assertTrue($response->headers->has('Deprecation'), 
            "Deprecation header missing. Status: {$statusCode}, All headers: " . json_encode($allHeaders));
        $this->assertEquals('true', $response->headers->get('Deprecation'));
        
        $this->assertTrue($response->headers->has('Sunset'), 'Sunset header should be present');
        $this->assertNotEmpty($response->headers->get('Sunset'));
        
        $this->assertTrue($response->headers->has('Link'), 'Link header should be present');
        $linkHeader = $response->headers->get('Link');
        $this->assertStringContainsString('/api/webhooks/stripe', $linkHeader);
        $this->assertStringContainsString('successor-version', $linkHeader);

        // Vérifier que le log warning a été écrit (sans payload) - seulement si status 2xx
        if ($statusCode >= 200 && $statusCode < 400) {
            Log::shouldHaveReceived('warning')
                ->with('Legacy webhook endpoint used', \Mockery::on(function ($context) {
                    return isset($context['route']) 
                        && isset($context['method'])
                        && isset($context['ip'])
                        && !isset($context['payload']) // Pas de payload
                        && !isset($context['signature']); // Pas de signature
                }));
        }
    }

    /**
     * Test que l'endpoint officiel ne retourne pas les headers de dépréciation
     */
    public function test_official_endpoint_does_not_return_deprecation_headers(): void
    {
        $response = $this->postJson('/api/webhooks/stripe', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
        ]);

        // L'endpoint officiel ne doit pas avoir les headers de dépréciation
        $this->assertFalse($response->headers->has('Deprecation'));
    }

    /**
     * Test que /payment/card/webhook retourne aussi les headers
     */
    public function test_payment_card_webhook_returns_deprecation_headers(): void
    {
        // Utiliser call() avec payload brut JSON pour éviter CSRF/session
        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode([
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
        ]));

        $statusCode = $response->getStatusCode();
        $this->assertNotEquals(419, $statusCode, "CSRF token mismatch - status: {$statusCode}");
        $this->assertNotEquals(302, $statusCode, "Unexpected redirect - status: {$statusCode}");

        $this->assertTrue($response->headers->has('Deprecation'), 
            "Deprecation header missing. Status: {$statusCode}");
        $this->assertEquals('true', $response->headers->get('Deprecation'));
    }
}




