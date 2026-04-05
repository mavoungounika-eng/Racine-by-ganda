<?php

namespace Tests\Integration\Services\Monitoring;

use App\Services\Monitoring\AlertService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

/**
 * AlertServiceIntegrationTest
 * 
 * Tests d'intégration pour AlertService avec mocks HTTP/Mail uniquement
 */
class AlertServiceIntegrationTest extends IntegrationTestCase
{
    protected AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock external dependencies only
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);
        Mail::fake();
        Log::spy();

        // Use real service
        $this->alertService = app(AlertService::class);
    }
    #[Test]
    public function sends_slack_notification(): void
    {
        // Arrange
        config(['alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        // Act
        $this->alertService->critical('Test Alert', 'This is a test message', ['test' => true]);

        // Assert - Slack should be called
        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/test' &&
                   isset($request['attachments']) &&
                   str_contains(json_encode($request['attachments']), 'Test Alert');
        });
    }
    #[Test]
    public function sends_email_notification(): void
    {
        // Arrange
        config(['alerts.email.recipients' => ['admin@racine.com']]);

        // Act
        $this->alertService->critical('Test Alert', 'This is a test message');

        // Assert - Email should be sent
        Mail::assertSent(\App\Mail\AlertNotification::class, function ($mail) {
            return $mail->hasTo('admin@racine.com');
        });
    }
    #[Test]
    public function always_logs_alerts(): void
    {
        // Act
        $this->alertService->info('Info Alert', 'Info message');

        // Assert - Should be logged
        Log::shouldHaveReceived('info')
            ->once()
            ->with(\Mockery::pattern('/\[ALERT\].*Info Alert/'), \Mockery::any());
    }
    #[Test]
    public function routes_by_severity_correctly(): void
    {
        // Arrange
        config([
            'alerts.slack.webhook_url' => 'https://hooks.slack.com/test',
            'alerts.email.recipients' => ['admin@racine.com'],
        ]);

        // Act - Critical should use all channels
        $this->alertService->critical('Critical', 'Critical message');

        // Assert
        Http::assertSentCount(1); // Slack
        Mail::assertSent(\App\Mail\AlertNotification::class, 1); // Email
        Log::shouldHaveReceived('critical')->once(); // Log

        // Reset
        Http::fake();
        Mail::fake();

        // Act - Info should only log
        $this->alertService->info('Info', 'Info message');

        // Assert
        Http::assertNothingSent(); // No Slack
        Mail::assertNothingSent(); // No Email
        Log::shouldHaveReceived('info')->once(); // Only log
    }
    #[Test]
    public function formats_message_correctly(): void
    {
        // Arrange
        config(['alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        // Act
        $this->alertService->warning('Warning Title', 'Warning message', [
            'queue' => 'webhooks',
            'threshold' => 500,
        ]);

        // Assert - Check payload structure
        Http::assertSent(function ($request) {
            $payload = $request->data();
            
            return isset($payload['username']) &&
                   isset($payload['attachments']) &&
                   isset($payload['attachments'][0]['title']) &&
                   isset($payload['attachments'][0]['fields']) &&
                   str_contains($payload['attachments'][0]['title'], 'Warning Title');
        });
    }
    #[Test]
    public function test_method_validates_all_channels(): void
    {
        // Arrange
        config([
            'alerts.slack.webhook_url' => 'https://hooks.slack.com/test',
            'alerts.email.recipients' => ['admin@racine.com'],
        ]);

        // Act
        $results = $this->alertService->test();

        // Assert
        $this->assertIsArray($results);
        $this->assertArrayHasKey('slack', $results);
        $this->assertArrayHasKey('email', $results);
        $this->assertArrayHasKey('log', $results);
        
        $this->assertEquals('success', $results['slack']);
        $this->assertEquals('success', $results['email']);
        $this->assertEquals('success', $results['log']);
    }
}
