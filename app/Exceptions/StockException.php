<?php

namespace App\Exceptions;

use Exception;

class StockException extends Exception
{
    protected $code;
    protected $userMessage;

    public function __construct(string $message = '', int $code = 400, ?string $userMessage = null)
    {
        parent::__construct($message, $code);
        $this->code = $code;
        $this->userMessage = $userMessage ?? 'Une erreur est survenue avec le stock.';
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $this->getUserMessage(),
                'code' => $this->code,
            ], $this->code);
        }

        return back()->with('error', $this->getUserMessage());
    }
}

