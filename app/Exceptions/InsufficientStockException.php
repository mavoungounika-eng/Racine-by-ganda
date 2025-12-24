<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $product;
    protected $requestedQuantity;
    protected $availableStock;

    public function __construct($product, $requestedQuantity, $availableStock)
    {
        $this->product = $product;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableStock = $availableStock;

        $message = "Stock insuffisant pour le produit '{$product->title}'. " .
                   "Demandé: {$requestedQuantity}, Disponible: {$availableStock}";

        parent::__construct($message);
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getRequestedQuantity()
    {
        return $this->requestedQuantity;
    }

    public function getAvailableStock()
    {
        return $this->availableStock;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Stock insuffisant',
                'message' => $this->getMessage(),
                'product_id' => $this->product->id,
                'requested' => $this->requestedQuantity,
                'available' => $this->availableStock,
            ], 400);
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->withInput();
    }
}
