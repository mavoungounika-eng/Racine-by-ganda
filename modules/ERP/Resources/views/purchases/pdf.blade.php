<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #ED5F1E; font-size: 24px; margin: 0; }
        .header p { margin: 2px 0; color: #666; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-ordered { background: #FFA500; color: white; }
        .badge-received { background: #28a745; color: white; }
        .badge-cancelled { background: #dc3545; color: white; }
        .info-grid { width: 100%; margin-bottom: 20px; }
        .info-grid td { padding: 5px 10px; vertical-align: top; width: 50%; }
        .info-box { border: 1px solid #ddd; border-radius: 4px; padding: 10px; }
        .info-box h3 { margin: 0 0 8px 0; font-size: 13px; color: #ED5F1E; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .info-row { margin: 4px 0; }
        .info-label { font-weight: bold; color: #555; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th { background: #ED5F1E; color: white; padding: 8px; text-align: left; }
        table.items td { padding: 8px; border-bottom: 1px solid #eee; }
        table.items tr:nth-child(even) td { background: #f9f9f9; }
        .total-row td { font-weight: bold; background: #f0f0f0 !important; border-top: 2px solid #ED5F1E; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .ref { font-size: 11px; color: #888; }
    </style>
</head>
<body>

<div class="header">
    <h1>RACINE BY GANDA</h1>
    <p>Bon de Commande Fournisseur</p>
    <p class="ref">Réf : {{ $purchase->reference }}</p>
    <p>
        @if($purchase->status === 'ordered')
            <span class="badge badge-ordered">Commandé</span>
        @elseif($purchase->status === 'received')
            <span class="badge badge-received">Réceptionné</span>
        @else
            <span class="badge badge-cancelled">Annulé</span>
        @endif
    </p>
</div>

<table class="info-grid">
    <tr>
        <td>
            <div class="info-box">
                <h3>Fournisseur</h3>
                @if($purchase->supplier)
                <div class="info-row"><span class="info-label">Nom :</span> {{ $purchase->supplier->name }}</div>
                @if($purchase->supplier->email)
                <div class="info-row"><span class="info-label">Email :</span> {{ $purchase->supplier->email }}</div>
                @endif
                @if($purchase->supplier->phone)
                <div class="info-row"><span class="info-label">Tél :</span> {{ $purchase->supplier->phone }}</div>
                @endif
                @else
                <div class="info-row">-</div>
                @endif
            </div>
        </td>
        <td>
            <div class="info-box">
                <h3>Commande</h3>
                <div class="info-row"><span class="info-label">Date :</span> {{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}</div>
                <div class="info-row"><span class="info-label">Livraison prévue :</span> {{ $purchase->expected_delivery_date?->format('d/m/Y') ?? '-' }}</div>
                <div class="info-row"><span class="info-label">Créé par :</span> {{ $purchase->user?->name ?? '-' }}</div>
            </div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Article</th>
            <th>Unité</th>
            <th>Quantité</th>
            <th>Prix Unitaire</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchase->items as $item)
        <tr>
            <td>{{ $item->purchasable?->name ?? 'Article supprimé' }}</td>
            <td>{{ $item->purchasable?->unit ?? '-' }}</td>
            <td>{{ number_format($item->quantity, 2) }}</td>
            <td>{{ number_format($item->unit_price, 0, ',', ' ') }} XAF</td>
            <td>{{ number_format($item->total_price, 0, ',', ' ') }} XAF</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="4" style="text-align:right">Total Général :</td>
            <td>{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</td>
        </tr>
    </tbody>
</table>

@if($purchase->notes)
<div style="margin-top:20px; padding:10px; border:1px solid #ddd; border-radius:4px;">
    <strong>Notes :</strong> {{ $purchase->notes }}
</div>
@endif

<div class="footer">
    <p>Document généré le {{ now()->format('d/m/Y à H:i') }} — RACINE BY GANDA</p>
</div>

</body>
</html>