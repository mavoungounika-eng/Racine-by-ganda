<?php

namespace Modules\Accounting\Exceptions;

use Exception;

/**
 * Exception levée lors d'une tentative de création directe d'AccountingEntry
 * 
 * Cette exception garantit que toute écriture comptable passe par LedgerService.
 */
class ForbiddenCreationException extends Exception
{
    public function __construct(string $message = null)
    {
        parent::__construct(
            $message ?? "Création directe d'AccountingEntry interdite. Utiliser LedgerService."
        );
    }
}
