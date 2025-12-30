<?php

namespace App\Exceptions;

use Exception;

class OrderException extends Exception
{
    protected $code;
    protected $userMessage;

    public function __construct(string $message = '', int $code = 400, ?string $userMessage = null)
    {
        parent::__construct($message, $code);
        $this->code = $code;
        $this->userMessage = $userMessage ?? 'Une erreur est survenue lors de la création de la commande.';
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

