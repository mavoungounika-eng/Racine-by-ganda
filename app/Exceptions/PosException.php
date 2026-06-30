<?php

namespace App\Exceptions;

use RuntimeException;

class PosException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'POS_ERROR',
        private readonly int $httpStatus = 400
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
