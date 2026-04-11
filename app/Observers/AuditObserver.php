<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Global Audit Observer
 * 
 * Automatically logs creation, updates, and deletion of sensitive models.
 * Used for Task 3/11 (Global Audit Trail).
 */
class AuditObserver
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->auditService->log(
            'created',
            class_basename($model),
            $model->getKey(),
            null,
            [
                'new_attributes' => $this->maskSensitiveAttributes($model, $model->getAttributes()),
            ]
        );
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        
        // Remove updated_at from changes if it's the only one
        if (count($changes) === 1 && isset($changes['updated_at'])) {
            return;
        }

        $this->auditService->log(
            'updated',
            class_basename($model),
            $model->getKey(),
            null,
            [
                'old_attributes' => $this->maskSensitiveAttributes($model, array_intersect_key($model->getOriginal(), $changes)),
                'new_attributes' => $this->maskSensitiveAttributes($model, $changes),
                'changes' => $this->maskSensitiveAttributes($model, $changes),
            ]
        );
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->auditService->log(
            'deleted',
            class_basename($model),
            $model->getKey(),
            null,
            [
                'old_attributes' => $this->maskSensitiveAttributes($model, $model->getAttributes()),
            ]
        );
    }

    /**
     * Mask sensitive attributes to prevent data leaks in audit logs.
     * 
     * @param Model $model
     * @param array $attributes
     * @return array
     */
    protected function maskSensitiveAttributes(Model $model, array $attributes): array
    {
        // Global sensitive keys to always mask regardless of the model
        $globalHidden = [
            'password', 
            'remember_token', 
            'two_factor_secret', 
            'two_factor_recovery_codes', 
            'stripe_id', 
            'trusted_device_token',
            'api_token',
            'access_token',
            'refresh_token',
            'bank_account_details'
        ];

        $hidden = array_merge($model->getHidden(), $globalHidden);

        foreach ($attributes as $key => $value) {
            if (in_array($key, $hidden) && $value !== null) {
                // Ensure we mask arrays properly if the attribute was cast to an array
                $attributes[$key] = '[REDACTED]';
            }
        }
        
        return $attributes;
    }
}
