<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class InvoiceService
{
    public function generateInvoiceNumber(Order $order): string
    {
        $date = $order->created_at->format('Ymd');
        $sequence = str_pad($order->id, 5, '0', STR_PAD_LEFT);
        return "FACT-{$date}-{$sequence}";
    }

    public function generatePdf(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing(['items.product', 'address', 'user', 'promoCode']);

        return Pdf::loadView('invoices.invoice-pdf', [
            'order'         => $order,
            'invoiceNumber' => $this->generateInvoiceNumber($order),
            'invoiceDate'   => now(),
        ])->setPaper('a4', 'portrait');
    }

    public function generateInvoiceHtml(Order $order): string
    {
        $order->loadMissing(['items.product', 'address', 'user', 'promoCode']);

        return View::make('invoices.invoice', [
            'order'         => $order,
            'invoiceNumber' => $this->generateInvoiceNumber($order),
            'invoiceDate'   => now(),
        ])->render();
    }
}

