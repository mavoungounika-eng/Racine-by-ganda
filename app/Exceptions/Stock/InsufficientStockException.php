<?php

namespace App\Exceptions\Stock;

use Exception;

/**
 * Thrown when attempting to consume more stock than available.
 * 
 * This is a CRITICAL business rule (R12) that prevents
 * production from consuming materials that don't physically exist.
 */
class InsufficientStockException extends Exception
{
    public static function forMaterial(
        string $materialReference,
        float $requested,
        float $available
    ): self {
        return new self(
            "Insufficient stock for material {$materialReference}: " .
            "Requested {$requested}, Available {$available}"
        );
    }

    public static function forMaterialWithUnit(
        string $materialReference,
        float $requested,
        float $available,
        string $unit
    ): self {
        return new self(
            "Insufficient stock for material {$materialReference}: " .
            "Requested {$requested} {$unit}, Available {$available} {$unit}"
        );
    }
}
