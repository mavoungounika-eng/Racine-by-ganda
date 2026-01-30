<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Audit Service
 * 
 * Logs critical actions for compliance and forensics.
 * Captures state changes and user actions.
 */
class AuditService
{
    /**
     * Log an action
     * 
     * @param string $action Action type (refund_created, stock_adjusted, etc.)
     * @param string $entityType Entity type (Order, Product, Payment, etc.)
     * @param int|string $entityId Entity ID
     * @param ?User $user User who performed the action (current user if null)
     * @param array $metadata Additional metadata (before/after, reason, etc.)
     */
    public function log(
        string $action,
        string $entityType,
        int|string $entityId,
        ?User $user = null,
        array $metadata = []
    ): AuditLog {
        $user = $user ?? Auth::user();

        return AuditLog::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (int) $entityId,
            'user_id' => $user?->id,
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log an order refund
     */
    public function logRefund(
        int $orderId,
        float $amount,
        ?string $reason = null,
        ?User $user = null,
        array $additionalData = []
    ): AuditLog {
        return $this->log(
            'refund_created',
            'Order',
            $orderId,
            $user,
            array_merge([
                'amount' => $amount,
                'reason' => $reason,
            ], $additionalData)
        );
    }

    /**
     * Log a stock adjustment
     */
    public function logStockAdjustment(
        int $productId,
        int $quantityChange,
        ?string $reason = null,
        ?User $user = null,
        array $additionalData = []
    ): AuditLog {
        return $this->log(
            'stock_adjusted',
            'Product',
            $productId,
            $user,
            array_merge([
                'quantity_change' => $quantityChange,
                'reason' => $reason,
            ], $additionalData)
        );
    }

    /**
     * Log a payment method change
     */
    public function logPaymentMethodChange(
        int $orderId,
        string $oldMethod,
        string $newMethod,
        ?User $user = null,
        array $additionalData = []
    ): AuditLog {
        return $this->log(
            'payment_method_changed',
            'Order',
            $orderId,
            $user,
            array_merge([
                'old_method' => $oldMethod,
                'new_method' => $newMethod,
            ], $additionalData)
        );
    }

    /**
     * Log a webhook manual retry
     */
    public function logWebhookRetry(
        int $webhookFailureId,
        string $provider,
        string $eventType,
        ?User $user = null,
        array $additionalData = []
    ): AuditLog {
        return $this->log(
            'webhook_retried',
            'WebhookFailure',
            $webhookFailureId,
            $user,
            array_merge([
                'provider' => $provider,
                'event_type' => $eventType,
            ], $additionalData)
        );
    }

    /**
     * Get the client IP address
     */
    private function getIpAddress(): ?string
    {
        return Request::ip();
    }

    /**
     * Get the User-Agent
     */
    private function getUserAgent(): ?string
    {
        return Request::userAgent();
    }
}
