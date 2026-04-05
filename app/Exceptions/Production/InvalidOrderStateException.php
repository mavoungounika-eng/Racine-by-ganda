<?php

namespace App\Exceptions\Production;

use Exception;

/**
 * Thrown when attempting an operation on a Production Order
 * that is in an invalid state for that operation.
 * 
 * Examples:
 * - Closing an order that is not 'in_progress'
 * - Calculating cost for a non-completed order
 */
class InvalidOrderStateException extends Exception
{
    public static function cannotClose(string $ofNumber, string $currentStatus): self
    {
        return new self(
            "Cannot close order {$ofNumber}: Status must be 'in_progress', current: {$currentStatus}"
        );
    }

    public static function cannotCalculateCost(string $ofNumber): self
    {
        return new self(
            "Cannot calculate cost for non-completed order {$ofNumber}"
        );
    }
}
