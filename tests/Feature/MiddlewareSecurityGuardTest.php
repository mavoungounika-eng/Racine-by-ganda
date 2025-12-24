<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tests de garde pour vérifier que les middlewares critiques sont actifs
 * 
 * Ces tests doivent TOUJOURS passer en production.
 * Si un test échoue, cela indique une faille de sécurité critique.
 */
class MiddlewareSecurityGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que les middlewares role, permission et 2fa sont enregistrés
     * 
     * Ce test échoue si un middleware critique est désactivé dans bootstrap/app.php
     */
    public function test_critical_middlewares_are_registered(): void
    {
        $middlewares = app('router')->getMiddleware();
        
        // Vérifier que les middlewares critiques sont enregistrés
        $this->assertArrayHasKey('role', $middlewares, 'Middleware "role" doit être enregistré');
        $this->assertArrayHasKey('permission', $middlewares, 'Middleware "permission" doit être enregistré');
        $this->assertArrayHasKey('2fa', $middlewares, 'Middleware "2fa" doit être enregistré');
        
        // Vérifier que les classes sont correctes
        $this->assertEquals(
            \App\Http\Middleware\CheckRole::class,
            $middlewares['role'],
            'Middleware "role" doit pointer vers CheckRole::class'
        );
        
        $this->assertEquals(
            \App\Http\Middleware\CheckPermission::class,
            $middlewares['permission'],
            'Middleware "permission" doit pointer vers CheckPermission::class'
        );
        
        $this->assertEquals(
            \App\Http\Middleware\TwoFactorMiddleware::class,
            $middlewares['2fa'],
            'Middleware "2fa" doit pointer vers TwoFactorMiddleware::class'
        );
    }

    /**
     * Test que les routes admin sont protégées par auth + admin + 2fa
     */
    public function test_admin_routes_are_protected(): void
    {
        $adminRoute = Route::getRoutes()->getByName('admin.dashboard');
        
        $this->assertNotNull($adminRoute, 'Route admin.dashboard doit exister');
        
        $middlewares = $adminRoute->middleware();
        
        // Vérifier que les middlewares critiques sont présents
        $this->assertContains('auth', $middlewares, 'Route admin doit avoir middleware auth');
        $this->assertContains('admin', $middlewares, 'Route admin doit avoir middleware admin');
        $this->assertContains('2fa', $middlewares, 'Route admin doit avoir middleware 2fa');
    }

    /**
     * Test que les routes ERP sont protégées par auth + can:access-erp + 2fa
     */
    public function test_erp_routes_are_protected(): void
    {
        $erpRoute = Route::getRoutes()->getByName('erp.dashboard');
        
        $this->assertNotNull($erpRoute, 'Route erp.dashboard doit exister');
        
        $middlewares = $erpRoute->middleware();
        
        // Vérifier que les middlewares critiques sont présents
        $this->assertContains('auth', $middlewares, 'Route ERP doit avoir middleware auth');
        $this->assertContains('2fa', $middlewares, 'Route ERP doit avoir middleware 2fa');
        
        // Vérifier que can:access-erp est présent (peut être dans un groupe)
        $hasAccessErp = false;
        foreach ($middlewares as $middleware) {
            if (str_contains($middleware, 'access-erp') || str_contains($middleware, 'can:')) {
                $hasAccessErp = true;
                break;
            }
        }
        $this->assertTrue($hasAccessErp, 'Route ERP doit avoir Gate can:access-erp');
    }

    /**
     * Test que les routes checkout sont protégées par auth + throttle
     */
    public function test_checkout_routes_are_protected(): void
    {
        $checkoutRoute = Route::getRoutes()->getByName('checkout.index');
        
        $this->assertNotNull($checkoutRoute, 'Route checkout.index doit exister');
        
        $middlewares = $checkoutRoute->middleware();
        
        // Vérifier que les middlewares critiques sont présents
        $this->assertContains('auth', $middlewares, 'Route checkout doit avoir middleware auth');
        
        // Vérifier que throttle est présent
        $hasThrottle = false;
        foreach ($middlewares as $middleware) {
            if (str_contains($middleware, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }
        $this->assertTrue($hasThrottle, 'Route checkout doit avoir middleware throttle');
    }

    /**
     * Test que les routes webhooks ne sont PAS protégées par auth (normal)
     * mais ont throttle
     */
    public function test_webhook_routes_have_throttle_but_not_auth(): void
    {
        $stripeRoute = Route::getRoutes()->getByName('api.webhooks.stripe');
        
        $this->assertNotNull($stripeRoute, 'Route api.webhooks.stripe doit exister');
        
        $middlewares = $stripeRoute->middleware();
        
        // Les webhooks ne doivent PAS avoir auth (appelés par les providers)
        $this->assertNotContains('auth', $middlewares, 'Route webhook ne doit PAS avoir middleware auth');
        
        // Mais doivent avoir throttle
        $hasThrottle = false;
        foreach ($middlewares as $middleware) {
            if (str_contains($middleware, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }
        $this->assertTrue($hasThrottle, 'Route webhook doit avoir middleware throttle');
    }

    /**
     * Test qu'un utilisateur non authentifié ne peut pas accéder aux routes admin
     */
    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('admin.dashboard'));
        
        // Doit rediriger vers login ou retourner 401/403
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 401 || $response->status() === 403,
            'Route admin doit être inaccessible sans authentification'
        );
    }

    /**
     * Test qu'un utilisateur non authentifié ne peut pas accéder aux routes checkout
     */
    public function test_unauthenticated_user_cannot_access_checkout_routes(): void
    {
        $response = $this->get(route('checkout.index'));
        
        // Doit rediriger vers login ou retourner 401/403
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 401 || $response->status() === 403,
            'Route checkout doit être inaccessible sans authentification'
        );
    }
}

