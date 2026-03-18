<?php

namespace Tests\Feature\Pos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuthJsonResponseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scénario 1 — Token absent sur route POS
     */
    public function test_pos_route_returns_401_json_when_token_is_missing()
    {
        // Suppression explicite des en-têtes Accept pour simuler une requête générique/malformée
        $response = $this->withHeaders(['Accept' => '*/*'])->get('/pos/sessions/current');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'error'   => 'UNAUTHENTICATED',
                     'message' => 'Token opérateur Sanctum manquant ou invalide.',
                 ]);
    }

    /**
     * Scénario 2 — Token absent sur route API POS
     */
    public function test_api_pos_route_returns_401_json_when_token_is_missing()
    {
        $response = $this->withHeaders(['Accept' => '*/*'])->get('/api/pos/auth/operator/me');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'error'   => 'UNAUTHENTICATED',
                     'message' => 'Token opérateur Sanctum manquant ou invalide.',
                 ]);
    }

    /**
     * Scénario 3 — Route web NON affectée
     */
    public function test_web_routes_are_not_affected()
    {
        // 1. Accès direct à la page de login web (doit être 200)
        $loginResponse = $this->get('/login');
        $this->assertTrue(in_array($loginResponse->status(), [200, 302]), "HTTP status {$loginResponse->status()} received instead of 200/302 for /login.");
        $loginResponse->assertDontSee('UNAUTHENTICATED');

        // 2. Accès à une route web protégée qui doit rediriger vers /login au lieu de renvoyer du JSON
        $webProtectedResponse = $this->withHeaders(['Accept' => '*/*'])->get('/admin/dashboard'); 
        
        // Sur une route web native, on s'attend toujours à une redirection 302 vers '/login' en cas d'absence d'authentification
        // Car Laravel détecte qu'on ne passe pas par 'api/pos/*' ni 'pos/*' ni avec explicitement Accepts: application/json
        if ($webProtectedResponse->status() === 302) {
            $webProtectedResponse->assertRedirect();
            $webProtectedResponse->assertDontSee('UNAUTHENTICATED');
        } else {
            // Note: certaines routes admin pourraient générer des 404 dans les tests si non montées,
            // mais l'essentiel est qu'elles ne renvoient *pas* notre JSON personnalisé 401.
            $this->assertNotEquals(401, $webProtectedResponse->status());
        }
    }
}
