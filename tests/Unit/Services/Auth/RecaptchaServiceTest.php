<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\RecaptchaService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration de base pour les tests
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.site_key', 'site-key');
        Config::set('recaptcha.secret_key', 'secret-key');
        Config::set('recaptcha.verify_url', 'https://www.google.com/recaptcha/api/siteverify');
        Config::set('recaptcha.threshold', 0.5);
        Config::set('recaptcha.skip_for_testing', false);
    }

    public function test_it_returns_true_when_disabled()
    {
        Config::set('recaptcha.enabled', false);
        
        $service = new RecaptchaService();
        $this->assertTrue($service->verify('token'));
    }

    public function test_it_returns_false_when_token_is_empty()
    {
        $service = new RecaptchaService();
        $this->assertFalse($service->verify(''));
    }

    public function test_it_returns_true_on_successful_verification()
    {
        Http::fake([
            'google.com/*' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'login',
            ], 200),
        ]);

        $service = new RecaptchaService();
        $this->assertTrue($service->verify('valid-token', 'login'));
    }

    public function test_it_returns_false_on_low_score()
    {
        Http::fake([
            'google.com/*' => Http::response([
                'success' => true,
                'score' => 0.1, // Below 0.5 threshold
                'action' => 'login',
            ], 200),
        ]);

        $service = new RecaptchaService();
        $this->assertFalse($service->verify('low-score-token', 'login'));
    }

    public function test_it_returns_false_on_invalid_token()
    {
        Http::fake([
            'google.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $service = new RecaptchaService();
        $this->assertFalse($service->verify('invalid-token'));
    }

    public function test_it_returns_true_on_api_failure_fail_open()
    {
        Http::fake([
            'google.com/*' => Http::response([], 500),
        ]);

        $service = new RecaptchaService();
        $this->assertTrue($service->verify('token'));
    }
}
