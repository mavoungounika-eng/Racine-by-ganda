<?php

namespace App\Exceptions\Production;

use Exception;

/**
 * Thrown when production output data is invalid.
 * 
 * Examples:
 * - Output with zero total quantity
 * - Missing required fields (variant_sku, qty_good)
 */
class InvalidProductionOutputException extends Exception
{
    public static function zeroTotalQuantity(string $variantSku): self
    {
        return new self(
            "Output for variant {$variantSku} has zero total quantity (good + second + rejected = 0)"
        );
    }

    public static function missingRequiredField(string $field): self
    {
        return new self(
            "Invalid output structure: {$field} is required"
        );
    }
}
