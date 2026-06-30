<?php

namespace App\Exceptions\Production;

use Exception;

/**
 * Thrown when attempting cost calculation without a BOM snapshot.
 * 
 * The BOM snapshot is the ONLY source of truth for cost calculation.
 * Using the current product BOM would create retroactive cost changes.
 */
class MissingBOMSnapshotException extends Exception
{
    public static function forOrder(string $ofNumber): self
    {
        return new self(
            "Order {$ofNumber} has no BOM snapshot. Cannot calculate real cost without frozen recipe."
        );
    }
}
