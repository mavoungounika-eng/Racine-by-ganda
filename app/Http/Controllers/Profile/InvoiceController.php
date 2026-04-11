<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

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
     * Télécharge la facture en HTML
     */
    public function download(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        $html = $this->invoiceService->generateInvoiceHtml($order);
        $invoiceNumber = $this->invoiceService->generateInvoiceNumber($order);
        $filename = "facture-{$invoiceNumber}.html";

        return Response::make($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Imprime la facture (version imprimable)
     */
    public function print(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        $order->load(['items.product', 'address', 'user']);
        
        $invoiceNumber = $this->invoiceService->generateInvoiceNumber($order);
        $invoiceDate = now();

        return view('invoices.invoice', compact('order', 'invoiceNumber', 'invoiceDate'));
    }
}
