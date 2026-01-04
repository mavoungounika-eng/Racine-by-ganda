<?php

namespace App\Exceptions\Production;

use Exception;

/**
 * Thrown when attempting to close a Production Order
 * without the required supporting data.
 * 
 * Examples:
 * - No material consumption logs
 * - No time logs (when operations exist)
 * - No outputs provided
 */
class MissingProductionDataException extends Exception
{
    public static function noMaterialLogs(string $ofNumber): self
    {
        return new self(
            "Cannot close order {$ofNumber}: No material consumption logged"
        );
    }

    public static function noTimeLogs(string $ofNumber): self
    {
        return new self(
            "Cannot close order {$ofNumber}: No time logs recorded"
        );
    }

    public static function noOutputs(string $ofNumber): self
    {
        return new self(
            "Cannot close order {$ofNumber}: No outputs provided"
        );
    }
}
