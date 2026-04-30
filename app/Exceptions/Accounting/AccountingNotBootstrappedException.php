<?php

namespace App\Exceptions\Accounting;

use Exception;

/**
 * AccountingNotBootstrappedException
 * 
 * Levée quand l'environnement comptable n'est pas prêt pour une opération POS.
 * 
 * AUDIT-FRIENDLY: Message standardisé pour traçabilité.
 */
class AccountingNotBootstrappedException extends Exception
{
    public function __construct(string $missingElement)
    {
        parent::__construct(
            "Accounting environment not bootstrapped. POS settlement blocked. Missing: {$missingElement}"
        );
    }
}
