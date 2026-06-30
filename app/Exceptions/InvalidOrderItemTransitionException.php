<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidOrderItemTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Invalid OrderItem transition: '{$from}' → '{$to}'.");
    }
}
