<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ $invoiceNumber }} - RACINE BY GANDA</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style nonce="{{ csp_nonce() }}">
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #2C1810;
            background: #f5f4f2;
            padding: 30px 20px;
        }

        .invoice-container {
            max-width: 820px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(22,13,12,0.08);
            overflow: hidden;
        }

        /* ── Navigation ── */
        .invoice-nav {
            padding: 16px 32px;
            background: #fff;
            border-bottom: 2px solid rgba(22,13,12,0.07);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .nav-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: rgba(22,13,12,0.6);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 500;
            padding: 7px 13px;
            border-radius: 8px;
            border: 1px solid rgba(22,13,12,0.15);
            transition: all 0.2s;
        }
        .nav-back:hover { color: #160D0C; border-color: rgba(22,13,12,0.3); background: rgba(22,13,12,0.03); }
        .nav-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 4px;
        }
        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid;
            text-decoration: none;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s;
        }
        .nav-btn--download {
            color: #0EA5E9;
            border-color: #0EA5E9;
        }
        .nav-btn--download:hover { background: rgba(14,165,233,0.06); }
        .nav-btn--print {
            color: #22C55E;
            border-color: #22C55E;
        }
        .nav-btn--print:hover { background: rgba(34,197,94,0.06); }

        /* ── Invoice body ── */
        .invoice-body {
            padding: 40px 40px 32px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 36px;
            padding-bottom: 24px;
            border-bottom: 3px solid #ED5F1E;
            gap: 24px;
        }

        .company-info h1 {
            font-size: 26px;
            color: #160D0C;
            margin-bottom: 8px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .company-info p { color: #6c757d; margin: 3px 0; font-size: 12px; }

        .invoice-info { text-align: right; flex-shrink: 0; }
        .invoice-info h2 { font-size: 22px; color: #ED5F1E; margin-bottom: 8px; font-weight: 700; }
        .invoice-info p { color: #6c757d; margin: 3px 0; font-size: 12px; }
        .invoice-number-badge {
            display: inline-block;
            background: rgba(237,95,30,0.08);
            color: #ED5F1E;
            border: 1px solid rgba(237,95,30,0.2);
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 36px;
        }
        .detail-section h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: rgba(22,13,12,0.4);
            font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(22,13,12,0.08);
        }
        .detail-section p { margin: 6px 0; color: #6c757d; font-size: 12px; }
        .detail-section strong { color: #2C1810; }

        /* ── Table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .items-table thead tr { background: #160D0C; }
        .items-table th {
            padding: 11px 14px;
            text-align: left;
            font-weight: 700;
            font-size: 10.5px;
            color: rgba(255,255,255,0.9);
            letter-spacing: 0.4px;
        }
        .items-table td {
            padding: 11px 14px;
            border-bottom: 1px solid rgba(22,13,12,0.07);
            font-size: 12px;
        }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table tbody tr:hover { background: rgba(237,95,30,0.025); }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        /* ── Total ── */
        .invoice-total {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }
        .total-box {
            width: 280px;
            background: rgba(22,13,12,0.025);
            border: 1px solid rgba(22,13,12,0.08);
            border-radius: 10px;
            padding: 16px 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(22,13,12,0.07);
            margin-bottom: 10px;
        }
        .total-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .total-row.final {
            font-size: 16px;
            font-weight: 800;
            color: #ED5F1E;
            margin-top: 6px;
            padding-top: 10px;
            border-top: 2px solid #ED5F1E;
        }

        /* ── Status badges ── */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 700;
            border: 1px solid;
        }
        .status-badge--paid    { background: rgba(34,197,94,0.1);  color: #16a34a; border-color: rgba(34,197,94,0.25); }
        .status-badge--pending { background: rgba(255,184,0,0.1);  color: #B45309; border-color: rgba(255,184,0,0.3);  }
        .status-badge--cancelled { background: rgba(220,38,38,0.1); color: #b91c1c; border-color: rgba(220,38,38,0.25); }

        /* ── Footer ── */
        .invoice-footer {
            margin: 32px 0 0;
            padding: 20px 40px;
            background: rgba(22,13,12,0.025);
            border-top: 1px solid rgba(22,13,12,0.08);
            text-align: center;
            color: rgba(22,13,12,0.4);
            font-size: 10.5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .footer-order-ref {
            font-size: 10px;
            font-weight: 700;
            color: rgba(22,13,12,0.3);
            border: 1px solid rgba(22,13,12,0.12);
            border-radius: 4px;
            padding: 2px 8px;
        }

        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; border-radius: 0; }
            .invoice-nav { display: none; }
            .invoice-body { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">

        {{-- NAVIGATION --}}
        <div class="invoice-nav">
            <a href="{{ route('profile.orders.show', $order) }}" class="nav-back">
                <i class="fas fa-arrow-left"></i> Retour à la commande
            </a>
            <div class="nav-actions">
                <a href="{{ route('profile.invoice.download', $order) }}" class="nav-btn nav-btn--download">
                    <i class="fas fa-download"></i> Télécharger
                </a>
                <button onclick="window.print()" class="nav-btn nav-btn--print">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </div>

        <div class="invoice-body">

            {{-- HEADER --}}
            <div class="invoice-header">
                <div class="company-info">
                    <h1>RACINE BY GANDA</h1>
                    <p>Boutique de Mode Africaine</p>
                    <p>Email: contact@racinebyganda.com</p>
                    <p>Téléphone: +242 XX XXX XX XX</p>
                </div>
                <div class="invoice-info">
                    <div class="invoice-number-badge">N° {{ $invoiceNumber }}</div>
                    <h2>FACTURE</h2>
                    <p><strong>Date:</strong> {{ $invoiceDate->format('d/m/Y') }}</p>
                    <p><strong>Commande:</strong> #{{ $order->id }}</p>
                </div>
            </div>

            {{-- DETAILS --}}
            <div class="invoice-details">
                <div class="detail-section">
                    <h3>Facturé à</h3>
                    <p><strong>{{ $order->customer_name }}</strong></p>
                    <p>{{ $order->customer_email }}</p>
                    @if($order->customer_phone)
                        <p>{{ $order->customer_phone }}</p>
                    @endif
                    @if($order->address)
                        <p style="margin-top:8px;">{{ $order->address->address_line_1 }}</p>
                        @if($order->address->address_line_2)
                            <p>{{ $order->address->address_line_2 }}</p>
                        @endif
                        <p>{{ $order->address->city }}{{ $order->address->postal_code ? ', ' . $order->address->postal_code : '' }}</p>
                        <p>{{ $order->address->country }}</p>
                    @elseif($order->customer_address)
                        <p style="margin-top:8px;">{{ $order->customer_address }}</p>
                    @endif
                </div>
                <div class="detail-section">
                    <h3>Informations de commande</h3>
                    @php
                        $statusLabels = [
                            'pending'    => 'En attente',
                            'processing' => 'En traitement',
                            'paid'       => 'Payée',
                            'completed'  => 'Complétée',
                            'delivered'  => 'Livrée',
                            'cancelled'  => 'Annulée',
                        ];
                        $statusLabel = $statusLabels[$order->status] ?? ucfirst($order->status);
                    @endphp
                    <p><strong>Date de commande:</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
                    <p><strong>Statut commande:</strong> {{ $statusLabel }}</p>
                    <p style="margin-top:8px;"><strong>Paiement :</strong>
                        @if($order->status === 'cancelled')
                            <span class="status-badge status-badge--cancelled"><i class="fas fa-ban me-1"></i>Annulée</span>
                        @elseif($order->payment_status === 'paid')
                            <span class="status-badge status-badge--paid"><i class="fas fa-check-circle me-1"></i>Payé</span>
                        @else
                            <span class="status-badge status-badge--pending"><i class="fas fa-clock me-1"></i>En attente</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- ITEMS --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="text-center">Quantité</th>
                        <th class="text-end">Prix unitaire</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td><strong>{{ $item->product->title ?? 'Produit' }}</strong></td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA</td>
                        <td class="text-end"><strong>{{ number_format(($item->price ?? 0) * $item->quantity, 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- TOTAL --}}
            <div class="invoice-total">
                <div class="total-box">
                    <div class="total-row final">
                        <span>Total TTC</span>
                        <span>{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>

        </div>{{-- /invoice-body --}}

        {{-- FOOTER --}}
        <div class="invoice-footer">
            <div>
                <strong>RACINE BY GANDA</strong> — Merci pour votre confiance !
            </div>
            <div style="text-align:center;color:rgba(22,13,12,0.3);font-size:10px;">
                Générée le {{ now()->format('d/m/Y à H:i') }}
            </div>
            <div>
                <span class="footer-order-ref">Commande #{{ $order->id }}</span>
            </div>
        </div>

    </div>
</body>
</html>
