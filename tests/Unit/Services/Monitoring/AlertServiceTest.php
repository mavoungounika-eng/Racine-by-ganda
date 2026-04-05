<?php

namespace Tests\Unit\Services\Monitoring;

use App\Services\Monitoring\AlertService;
use App\Mail\AlertNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    protected AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alertService = new AlertService();
    }
    #[Test]
    public function sends_slack_notification()
    {
        // Arrange
        Http::fake([
            'hooks.slack.com/*' => Http::response(['ok' => true], 200)
        ]);

        config(['alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        // Act
        $this->alertService->critical('Test Alert', 'Test message', ['key' => 'value']);

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com')
                && $request['text'] !== null;
        });
    }
    #[Test]
    public function sends_email_notification()
    {
        // Arrange
        Mail::fake();
        
        config(['alerts.email.recipients' => 'admin@racine.com,ops@racine.com']);

        // Act
        $this->alertService->critical('Test Alert', 'Test message');

        // Assert
        Mail::assertSent(AlertNotification::class, function ($mail) {
            return $mail->hasTo('admin@racine.com')
                && $mail->hasTo('ops@racine.com');
        });
    }
    #[Test]
    public function logs_alert()
    {
        // Arrange
        Log::spy();

        // Act
        $this->alertService->info('Test Info', 'Info message');

        // Assert
        Log::shouldHaveReceived('info')
            ->once()
            ->with(\Mockery::pattern('/\[ALERT\]/'));
    }
    #[Test]
    public function critical_alert_uses_all_channels()
    {
        // Arrange
        Http::fake();
        Mail::fake();
        Log::spy();

        config([
            'alerts.slack.webhook_url' => 'https://hooks.slack.com/test',
            'alerts.email.recipients' => 'admin@racine.com'
        ]);

        // Act
        $this->alertService->critical('Critical Alert', 'Critical message');

        // Assert
        Http::assertSentCount(1); // Slack
        Mail::assertSent(AlertNotification::class); // Email
        Log::shouldHaveReceived('critical')->once(); // Log
    }
    #[Test]
    public function high_alert_uses_slack_and_logs()
    {
        // Arrange
        Http::fake();
        Mail::fake();
        Log::spy();

        config(['alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        // Act
        $this->alertService->high('High Alert', 'High priority message');

        // Assert
        Http::assertSentCount(1); // Slack
        Mail::assertNothingSent(); // No email for high
        Log::shouldHaveReceived('error')->once(); // Log
    }
    #[Test]
    public function warning_alert_uses_slack_and_logs()
    {
        // Arrange
        Http::fake();
        Mail::fake();
        Log::spy();

        config(['alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        // Act
        $this->alertService->warning('Warning Alert', 'Warning message');

        // Assert
        Http::assertSentCount(1); // Slack
        Mail::assertNothingSent(); // No email for warning
        Log::shouldHaveReceived('warning')->once(); // Log
    }
    #[Test]
    public function info_alert_uses_logs_only()
    {
        // Arrange
        Http::fake();
        Mail::fake();
        Log::spy();

        // Act
        $this->alertService->info('Info Alert', 'Info message');

        // Assert
        Http::assertNothingSent(); // No Slack for info
        Mail::assertNothingSent(); // No email for info
        Log::shouldHaveReceived('info')->once(); // Log only
    }
    #[Test]
    public function slack_message_format()
    {
        // Arrange
        Http::fake();

        config(['alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        // Act
        $this->alertService->critical(
            'Test Alert',
            'Test message',
            ['queue' => 'default', 'count' => 5]
        );

        // Assert
        Http::assertSent(function ($request) {
            $body = $request->data();
            
            return isset($body['text'])
                && isset($body['attachments'])
                && $body['attachments'][0]['color'] === 'danger' // Critical = red
                && isset($body['attachments'][0]['fields']);
        });
    }
    #[Test]
    public function email_subject_includes_severity()
    {
        // Arrange
        Mail::fake();
        
        config(['alerts.email.recipients' => 'admin@racine.com']);

        // Act
        $this->alertService->critical('Database Down', 'Cannot connect');

        // Assert
        Mail::assertSent(AlertNotification::class, function ($mail) {
            return str_contains($mail->subject, 'CRITICAL')
                && str_contains($mail->subject, 'Database Down');
        });
    }
    #[Test]
    public function respects_alerts_enabled_config()
    {
        // Arrange
        Http::fake();
        Mail::fake();
        Log::spy();

        config(['alerts.enabled' => false]);

        // Act
        $this->alertService->critical('Test Alert', 'Should not send');

        // Assert
        Http::assertNothingSent();
        Mail::assertNothingSent();
        // Logs should still work even when alerts disabled
        Log::shouldHaveReceived('critical')->once();
    }
    #[Test]
    public function handles_missing_slack_webhook()
    {
        // Arrange
        Http::fake();
        Log::spy();

        config(['alerts.slack.webhook_url' => null]);

        // Act - Should not throw exception
        $this->alertService->critical('Test Alert', 'Test message');

        // Assert
        Http::assertNothingSent();
        Log::shouldHaveReceived('critical')->once(); // Still logs
    }
    #[Test]
    public function handles_missing_email_recipients()
    {
        // Arrange
        Mail::fake();
        Log::spy();

        config(['alerts.email.recipients' => null]);

        // Act - Should not throw exception
        $this->alertService->critical('Test Alert', 'Test message');

        // Assert
        Mail::assertNothingSent();
        Log::shouldHaveReceived('critical')->once(); // Still logs
    }
    #[Test]
    public function test_method_validates_all_channels()
    {
        // Arrange
        Http::fake();
        Mail::fake();
        Log::spy();

        config([
            'alerts.enabled' => true,
            'alerts.slack.webhook_url' => 'https://hooks.slack.com/test',
            'alerts.email.recipients' => 'test@example.com'
        ]);

        // Act
        $result = $this->alertService->test();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('slack', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('log', $result);

        Http::assertSentCount(1);
        Mail::assertSent(AlertNotification::class);
        Log::shouldHaveReceived('info')->once();
    }
}
