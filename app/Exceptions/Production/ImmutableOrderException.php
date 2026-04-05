<?php

namespace App\Exceptions\Production;

use Exception;

/**
 * Thrown when attempting to modify immutable production data.
 * 
 * Examples:
 * - Modifying a completed Production Order
 * - Deleting a completed Production Order
 * - Changing BOM snapshot after order creation
 */
class ImmutableOrderException extends Exception
{
    public static function cannotModifyCompleted(string $ofNumber): self
    {
        return new self(
            "Cannot modify completed order {$ofNumber}. Completed orders are immutable for accounting integrity."
        );
    }

    public static function cannotDeleteCompleted(string $ofNumber): self
    {
        return new self(
            "Cannot delete completed order {$ofNumber}. Completed orders are immutable for traceability."
        );
    }

    public static function cannotModifyBOMSnapshot(string $ofNumber): self
    {
        return new self(
            "Cannot modify BOM snapshot for order {$ofNumber}. Snapshot is frozen at order creation."
        );
    }
}
