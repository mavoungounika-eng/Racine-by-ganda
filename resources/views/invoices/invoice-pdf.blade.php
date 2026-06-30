<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoiceNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #2C1810;
            background: white;
        }

        .page {
            padding: 30px 40px;
        }

        /* HEADER */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #ED5F1E;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-right { text-align: right; }

        .brand-name {
            font-size: 22px;
            font-weight: bold;
            color: #160D0C;
            letter-spacing: 1px;
        }
        .brand-sub {
            font-size: 10px;
            color: #8B7355;
            margin-top: 3px;
        }
        .brand-contact {
            font-size: 10px;
            color: #6c757d;
            margin-top: 6px;
            line-height: 1.7;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #ED5F1E;
            letter-spacing: 2px;
        }
        .invoice-meta {
            font-size: 10px;
            color: #6c757d;
            margin-top: 6px;
            line-height: 1.8;
        }
        .invoice-meta strong { color: #2C1810; }

        /* DETAILS */
        .details {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .detail-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .detail-col:last-child { padding-right: 0; }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #160D0C;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #ED5F1E;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .detail-line {
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .detail-line strong { color: #2C1810; }

        /* STATUS BADGE */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-paid    { background: #D1FAE5; color: #065F46; }
        .badge-pending { background: #FEF3C7; color: #92400E; }

        /* TABLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead tr {
            background: #160D0C;
            color: white;
        }
        .items-table th {
            padding: 9px 10px;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }
        .items-table th.right { text-align: right; }
        .items-table td {
            padding: 9px 10px;
            font-size: 10px;
            border-bottom: 1px solid #f0f0f0;
            color: #2C1810;
        }
        .items-table td.right { text-align: right; }
        .items-table td.center { text-align: center; }
        .items-table tbody tr:nth-child(even) { background: #FAFAFA; }

        .product-sku {
            font-size: 9px;
            color: #8B7355;
            margin-top: 2px;
        }

        /* TOTALS */
        .totals-wrapper {
            text-align: right;
            margin-bottom: 30px;
        }
        .totals-box {
            display: inline-block;
            width: 260px;
            background: #F8F4F1;
            border-radius: 6px;
            padding: 14px 16px;
        }
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 7px;
        }
        .total-row:last-child { margin-bottom: 0; }
        .total-label, .total-value {
            display: table-cell;
            font-size: 10px;
            color: #6c757d;
        }
        .total-value { text-align: right; }
        .total-row.final .total-label,
        .total-row.final .total-value {
            font-size: 13px;
            font-weight: bold;
            color: #ED5F1E;
            padding-top: 8px;
            border-top: 2px solid #ED5F1E;
        }
        .total-row.separator .total-label,
        .total-row.separator .total-value {
            padding-top: 7px;
            border-top: 1px solid #e0e0e0;
        }

        /* FOOTER */
        .footer {
            border-top: 1px solid #e0e0e0;
            padding-top: 14px;
            text-align: center;
            color: #8B7355;
            font-size: 9px;
            line-height: 1.7;
        }

        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="header-left">
            <div class="brand-name">RACINE BY GANDA</div>
            <div class="brand-sub">Boutique de Mode Africaine</div>
            <div class="brand-contact">
                contact@racinebyganda.com<br>
                République du Congo — Pointe-Noire, Galerie NF
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">FACTURE</div>
            <div class="invoice-meta">
                <strong>N° :</strong> {{ $invoiceNumber }}<br>
                <strong>Date :</strong> {{ $invoiceDate->format('d/m/Y') }}<br>
                <strong>Commande :</strong> #{{ $order->order_number ?? $order->id }}
            </div>
        </div>
    </div>

    {{-- DETAILS --}}
    <div class="details">
        <div class="detail-col">
            <div class="section-title">Facturé à</div>
            <div class="detail-line"><strong>{{ $order->customer_name }}</strong></div>
            <div class="detail-line">{{ $order->customer_email }}</div>
            @if($order->customer_phone)
                <div class="detail-line">{{ $order->customer_phone }}</div>
            @endif
            @if($order->address)
                <div class="detail-line" style="margin-top:6px;">{{ $order->address->address_line_1 }}</div>
                @if($order->address->address_line_2)
                    <div class="detail-line">{{ $order->address->address_line_2 }}</div>
                @endif
                <div class="detail-line">
                    {{ $order->address->city }}{{ $order->address->postal_code ? ' — ' . $order->address->postal_code : '' }}
                </div>
                <div class="detail-line">{{ $order->address->country }}</div>
            @elseif($order->customer_address)
                <div class="detail-line" style="margin-top:6px;">{{ $order->customer_address }}</div>
            @endif
        </div>

        <div class="detail-col">
            <div class="section-title">Informations commande</div>
            <div class="detail-line"><strong>Date :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</div>
            <div class="detail-line"><strong>Mode de livraison :</strong>
                {{ $order->shipping_method === 'home_delivery' ? 'Livraison à domicile' : 'Retrait en showroom' }}
            </div>
            <div class="detail-line"><strong>Paiement :</strong>
                @php
                    $paymentLabels = [
                        'card' => 'Carte bancaire',
                        'mobile_money' => 'Mobile Money',
                        'monetbil' => 'Monetbil',
                        'cash_on_delivery' => 'Paiement à la livraison',
                    ];
                @endphp
                {{ $paymentLabels[$order->payment_method] ?? ucfirst($order->payment_method ?? '') }}
            </div>
            <div class="detail-line"><strong>Statut paiement :</strong>
                <span class="badge {{ $order->payment_status === 'paid' ? 'badge-paid' : 'badge-pending' }}">
                    {{ $order->payment_status === 'paid' ? 'Payé' : 'En attente' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ITEMS --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:50%">Produit</th>
                <th class="right" style="width:15%">Prix unit.</th>
                <th class="right" style="width:10%">Qté</th>
                <th class="right" style="width:25%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product->title ?? $item->product_name ?? 'Produit' }}</strong>
                    @if($item->product && !empty($item->product->sku))
                        <div class="product-sku">Réf. {{ $item->product->sku }}</div>
                    @endif
                </td>
                <td class="right">{{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right"><strong>{{ number_format(($item->price ?? 0) * $item->quantity, 0, ',', ' ') }} FCFA</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="totals-wrapper">
        <div class="totals-box">
            @php
                $subtotal = $order->items->sum(fn($i) => ($i->price ?? 0) * $i->quantity);
            @endphp
            <div class="total-row">
                <span class="total-label">Sous-total</span>
                <span class="total-value">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="total-row">
                <span class="total-label">Remise promo</span>
                <span class="total-value" style="color:#ED5F1E;">- {{ number_format($order->discount_amount, 0, ',', ' ') }} FCFA</span>
            </div>
            @endif
            <div class="total-row separator">
                <span class="total-label">Livraison</span>
                <span class="total-value">
                    {{ $order->shipping_cost > 0 ? number_format($order->shipping_cost, 0, ',', ' ') . ' FCFA' : 'Offerte' }}
                </span>
            </div>
            <div class="total-row final">
                <span class="total-label">Total TTC</span>
                <span class="total-value">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <strong>RACINE BY GANDA</strong> — Merci pour votre confiance !<br>
        Facture générée le {{ now()->format('d/m/Y à H:i') }} · Document non contractuel<br>
        SIRET / NIF : — · contact@racinebyganda.com
    </div>

</div>
</body>
</html>
