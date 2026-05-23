<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Affiche la facture en HTML
     */
    public function show(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        $order->load(['items.product', 'address', 'user']);
        
        $invoiceNumber = $this->invoiceService->generateInvoiceNumber($order);
        $invoiceDate = now();

        return view('invoices.invoice', compact('order', 'invoiceNumber', 'invoiceDate'));
    }

    /**
     * Télécharge la facture en PDF
     */
    public function download(Order $order)
    {
        $this->authorize('view', $order);

        $invoiceNumber = $this->invoiceService->generateInvoiceNumber($order);
        $filename = "facture-{$invoiceNumber}.pdf";

        return $this->invoiceService->generatePdf($order)->download($filename);
    }

    /**
     * Affiche la facture PDF dans le navigateur
     */
    public function print(Order $order)
    {
        $this->authorize('view', $order);

        $invoiceNumber = $this->invoiceService->generateInvoiceNumber($order);
        $filename = "facture-{$invoiceNumber}.pdf";

        return $this->invoiceService->generatePdf($order)->stream($filename);
    }
}
