<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Génère le HTML de la facture
     */
    public function generateInvoiceHtml(Order $order): string
    {
        $order->load(['items.product', 'address', 'user']);
        
        return View::make('invoices.invoice', [
            'order' => $order,
            'invoiceNumber' => $this->generateInvoiceNumber($order),
            'invoiceDate' => now(),
        ])->render();
    }

    /**
     * Génère un numéro de facture unique
     */
    public function generateInvoiceNumber(Order $order): string
    {
        // Format: FACT-YYYYMMDD-XXXXX
        $date = $order->created_at->format('Ymd');
        $sequence = str_pad($order->id, 5, '0', STR_PAD_LEFT);
        return "FACT-{$date}-{$sequence}";
    }

    /**
     * Sauvegarde la facture HTML dans le storage
     */
    public function saveInvoice(Order $order): string
    {
        $html = $this->generateInvoiceHtml($order);
        $filename = "invoices/invoice-{$order->id}-" . now()->format('Y-m-d') . '.html';
        
        Storage::disk('public')->put($filename, $html);
        
        return $filename;
    }

    /**
     * Retourne le chemin public de la facture
     */
    public function getInvoicePath(Order $order): string
    {
        $filename = "invoices/invoice-{$order->id}-" . now()->format('Y-m-d') . '.html';
        
        if (!Storage::disk('public')->exists($filename)) {
            $this->saveInvoice($order);
        }
        
        return Storage::disk('public')->url($filename);
    }
}

