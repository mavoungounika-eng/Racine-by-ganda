<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ $invoiceNumber }} - RACINE BY GANDA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #2C1810;
            background: white;
            padding: 40px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #ED5F1E;
        }
        
        .company-info h1 {
            font-size: 28px;
            color: #2C1810;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .company-info p {
            color: #6c757d;
            margin: 5px 0;
        }
        
        .invoice-info {
            text-align: right;
        }
        
        .invoice-info h2 {
            font-size: 24px;
            color: #ED5F1E;
            margin-bottom: 10px;
        }
        
        .invoice-info p {
            color: #6c757d;
            margin: 5px 0;
        }
        
        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .detail-section h3 {
            font-size: 16px;
            color: #2C1810;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .detail-section p {
            margin: 8px 0;
            color: #6c757d;
        }
        
        .detail-section strong {
            color: #2C1810;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table thead {
            background: #2C1810;
            color: white;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .items-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .invoice-total {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .total-box {
            width: 300px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .total-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .total-row.final {
            font-size: 18px;
            font-weight: 700;
            color: #ED5F1E;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #ED5F1E;
        }
        
        .invoice-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
            color: #8B7355;
            font-size: 11px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .status-paid {
            background: rgba(34, 197, 94, 0.1);
            color: #22C55E;
        }
        
        .status-pending {
            background: rgba(255, 184, 0, 0.1);
            color: #FFB800;
        }
        
        @media print {
            body {
                padding: 20px;
            }
            
            .invoice-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- NAVIGATION -->
        <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0;">
            <a href="{{ route('profile.orders.show', $order) }}" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #ED5F1E; text-decoration: none; font-weight: 500; transition: all 0.3s;">
                <i class="fas fa-arrow-left"></i>
                <span>Retour à la commande</span>
            </a>
            <div style="display: inline-flex; gap: 0.5rem; margin-left: 1rem;">
                <a href="{{ route('profile.invoice.download', $order) }}" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #0EA5E9; text-decoration: none; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid #0EA5E9; border-radius: 6px; transition: all 0.3s;">
                    <i class="fas fa-download"></i>
                    <span>Télécharger</span>
                </a>
                <button onclick="window.print()" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #22C55E; background: white; border: 1px solid #22C55E; border-radius: 6px; padding: 0.5rem 1rem; font-weight: 500; cursor: pointer; transition: all 0.3s;">
                    <i class="fas fa-print"></i>
                    <span>Imprimer</span>
                </button>
            </div>
        </div>
        
        <!-- HEADER -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>RACINE BY GANDA</h1>
                <p>Boutique de Mode Africaine</p>
                <p>Email: contact@racinebyganda.com</p>
                <p>Téléphone: +242 XX XXX XX XX</p>
            </div>
            <div class="invoice-info">
                <h2>FACTURE</h2>
                <p><strong>N°:</strong> {{ $invoiceNumber }}</p>
                <p><strong>Date:</strong> {{ $invoiceDate->format('d/m/Y') }}</p>
                <p><strong>Commande:</strong> #{{ $order->id }}</p>
            </div>
        </div>
        
        <!-- DETAILS -->
        <div class="invoice-details">
            <div class="detail-section">
                <h3>Facturé à</h3>
                <p><strong>{{ $order->customer_name }}</strong></p>
                <p>{{ $order->customer_email }}</p>
                @if($order->customer_phone)
                <p>{{ $order->customer_phone }}</p>
                @endif
                @if($order->address)
                <p style="margin-top: 10px;">{{ $order->address->address_line_1 }}</p>
                @if($order->address->address_line_2)
                <p>{{ $order->address->address_line_2 }}</p>
                @endif
                <p>{{ $order->address->city }}{{ $order->address->postal_code ? ', ' . $order->address->postal_code : '' }}</p>
                <p>{{ $order->address->country }}</p>
                @elseif($order->customer_address)
                <p style="margin-top: 10px;">{{ $order->customer_address }}</p>
                @endif
            </div>
            <div class="detail-section">
                <h3>Informations de commande</h3>
                <p><strong>Date de commande:</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
                <p><strong>Statut:</strong> 
                    @php
                        $statusLabels = [
                            'pending' => 'En attente',
                            'processing' => 'En traitement',
                            'paid' => 'Payée',
                            'completed' => 'Complétée',
                            'delivered' => 'Livrée',
                            'cancelled' => 'Annulée',
                        ];
                        $statusLabel = $statusLabels[$order->status] ?? ucfirst($order->status);
                    @endphp
                    {{ $statusLabel }}
                </p>
                <p><strong>Paiement:</strong> 
                    <span class="status-badge {{ $order->payment_status === 'paid' ? 'status-paid' : 'status-pending' }}">
                        {{ $order->payment_status === 'paid' ? 'Payé' : 'En attente' }}
                    </span>
                </p>
            </div>
        </div>
        
        <!-- ITEMS -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-right">Prix unitaire</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product->title ?? 'Produit' }}</strong>
                        @if($item->product && $item->product->sku)
                        <br><small style="color: #8B7355;">SKU: {{ $item->product->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA</td>
                    <td class="text-right"><strong>{{ number_format(($item->price ?? 0) * $item->quantity, 0, ',', ' ') }} FCFA</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- TOTAL -->
        <div class="invoice-total">
            <div class="total-box">
                <div class="total-row final">
                    <span>Total TTC</span>
                    <span>{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="invoice-footer">
            <p><strong>RACINE BY GANDA</strong> - Merci pour votre confiance !</p>
            <p>Cette facture a été générée automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>

