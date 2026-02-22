<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\AlertNotification;

/**
 * AlertService - Gestion des alertes système
 * 
 * Envoie des notifications via:
 * - Slack (webhooks)
 * - Email
 * - Logs
 * 
 * Utilisé pour alertes critiques (circuit breaker, queues, erreurs)
 */
class AlertService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('alerts', []);
    }

    /**
     * Envoyer une alerte
     * 
     * @param string $severity critical|high|warning|info
     * @param string $title Titre de l'alerte
     * @param string $message Message détaillé
     * @param array $context Contexte additionnel
     * @param array $channels Canaux spécifiques (override config)
     */
    public function send(
        string $severity,
        string $title,
        string $message,
        array $context = [],
        ?array $channels = null
    ): void {
        $channels = $channels ?? $this->getChannelsForSeverity($severity);

        $alert = [
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
        ];

        foreach ($channels as $channel) {
            try {
                match ($channel) {
                    'slack' => $this->sendToSlack($alert),
                    'email' => $this->sendToEmail($alert),
                    'log' => $this->sendToLog($alert),
                    default => Log::warning("Unknown alert channel: {$channel}"),
                };
            } catch (\Exception $e) {
                Log::error('[ALERT SERVICE] Failed to send alert', [
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                    'alert' => $alert,
                ]);
            }
        }
    }

    /**
     * Alerte critique (circuit breaker open, service down, etc.)
     */
    public function critical(string $title, string $message, array $context = []): void
    {
        $this->send('critical', $title, $message, $context);
    }

    /**
     * Alerte haute priorité (erreurs fréquentes, performance dégradée)
     */
    public function high(string $title, string $message, array $context = []): void
    {
        $this->send('high', $title, $message, $context);
    }

    /**
     * Alerte warning (seuils approchés, anomalies)
     */
    public function warning(string $title, string $message, array $context = []): void
    {
        $this->send('warning', $title, $message, $context);
    }

    /**
     * Alerte info (événements notables)
     */
    public function info(string $title, string $message, array $context = []): void
    {
        $this->send('info', $title, $message, $context);
    }

    /**
     * Envoyer vers Slack
     */
    protected function sendToSlack(array $alert): void
    {
        $webhookUrl = config('alerts.slack.webhook_url');

        if (!$webhookUrl) {
            Log::debug('[ALERT SERVICE] Slack webhook not configured');
            return;
        }

        $color = match ($alert['severity']) {
            'critical' => '#FF0000',
            'high' => '#FF6600',
            'warning' => '#FFAA00',
            'info' => '#36A64F',
            default => '#808080',
        };

        $emoji = match ($alert['severity']) {
            'critical' => ':rotating_light:',
            'high' => ':warning:',
            'warning' => ':large_orange_diamond:',
            'info' => ':information_source:',
            default => ':bell:',
        };

        $payload = [
            'username' => 'RACINE Monitoring',
            'icon_emoji' => ':chart_with_upwards_trend:',
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "{$emoji} {$alert['title']}",
                    'text' => $alert['message'],
                    'fields' => [
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($alert['severity']),
                            'short' => true,
                        ],
                        [
                            'title' => 'Environment',
                            'value' => $alert['environment'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Timestamp',
                            'value' => $alert['timestamp'],
                            'short' => false,
                        ],
                    ],
                    'footer' => 'RACINE BY GANDA',
                    'ts' => now()->timestamp,
                ],
            ],
        ];

        // Ajouter contexte si présent
        if (!empty($alert['context'])) {
            $contextText = '';
            foreach ($alert['context'] as $key => $value) {
                $contextText .= "*{$key}:* " . (is_scalar($value) ? $value : json_encode($value)) . "\n";
            }
            $payload['attachments'][0]['fields'][] = [
                'title' => 'Context',
                'value' => $contextText,
                'short' => false,
            ];
        }

        Http::post($webhookUrl, $payload);
    }

    /**
     * Envoyer par email
     */
    protected function sendToEmail(array $alert): void
    {
        $recipients = config('alerts.email.recipients', []);

        if (empty($recipients)) {
            Log::debug('[ALERT SERVICE] No email recipients configured');
            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new AlertNotification($alert));
        }
    }

    /**
     * Envoyer vers logs
     */
    protected function sendToLog(array $alert): void
    {
        $level = match ($alert['severity']) {
            'critical' => 'critical',
            'high' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'notice',
        };

        Log::$level('[ALERT] ' . $alert['title'], [
            'message' => $alert['message'],
            'context' => $alert['context'],
            'timestamp' => $alert['timestamp'],
        ]);
    }

    /**
     * Obtenir canaux pour une sévérité
     */
    protected function getChannelsForSeverity(string $severity): array
    {
        return match ($severity) {
            'critical' => ['slack', 'email', 'log'],
            'high' => ['slack', 'log'],
            'warning' => ['slack', 'log'],
            'info' => ['log'],
            default => ['log'],
        };
    }

    /**
     * Tester les canaux de notification
     */
    public function test(): array
    {
        $results = [];

        // Test Slack
        try {
            $this->sendToSlack([
                'severity' => 'info',
                'title' => 'Test Alert',
                'message' => 'This is a test alert from RACINE monitoring system',
                'context' => ['test' => true],
                'timestamp' => now()->toIso8601String(),
                'environment' => config('app.env'),
            ]);
            $results['slack'] = 'success';
        } catch (\Exception $e) {
            $results['slack'] = 'failed: ' . $e->getMessage();
        }

        // Test Email
        try {
            $this->sendToEmail([
                'severity' => 'info',
                'title' => 'Test Alert',
                'message' => 'This is a test alert from RACINE monitoring system',
                'context' => ['test' => true],
                'timestamp' => now()->toIso8601String(),
                'environment' => config('app.env'),
            ]);
            $results['email'] = 'success';
        } catch (\Exception $e) {
            $results['email'] = 'failed: ' . $e->getMessage();
        }

        // Test Log
        try {
            $this->sendToLog([
                'severity' => 'info',
                'title' => 'Test Alert',
                'message' => 'This is a test alert from RACINE monitoring system',
                'context' => ['test' => true],
                'timestamp' => now()->toIso8601String(),
                'environment' => config('app.env'),
            ]);
            $results['log'] = 'success';
        } catch (\Exception $e) {
            $results['log'] = 'failed: ' . $e->getMessage();
        }

        return $results;
    }
}
