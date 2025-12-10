<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #4F46E5;">RACINE BY GANDA</h1>
    </div>

    <h2 style="color: #4F46E5;">Confirmation de votre commande</h2>
    
    <p>Bonjour {{ $order->customer_name }},</p>
    
    <p>Nous avons bien reçu votre commande <strong>#{{ $order->id }}</strong> et nous vous en remercions !</p>

    <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3 style="margin-top: 0;">Détails de la commande</h3>
        <p><strong>Numéro de commande :</strong> #{{ $order->id }}</p>
        <p><strong>Date :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
        <p><strong>Montant total :</strong> {{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</p>
        <p><strong>Statut :</strong> {{ ucfirst($order->status) }}</p>
    </div>

    <h3>Articles commandés</h3>
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr style="background: #4F46E5; color: white;">
                <th style="padding: 10px; text-align: left;">Produit</th>
                <th style="padding: 10px; text-align: center;">Qté</th>
                <th style="padding: 10px; text-align: right;">Prix</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px;">{{ $item->product->title ?? 'Produit' }}</td>
                <td style="padding: 10px; text-align: center;">{{ $item->quantity }}</td>
                <td style="padding: 10px; text-align: right;">{{ number_format($item->price * $item->quantity, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f5f5f5;">
                <td colspan="2" style="padding: 10px; text-align: right;">Total :</td>
                <td style="padding: 10px; text-align: right;">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>

    <p>Nous vous tiendrons informé(e) de l'avancement de votre commande par email.</p>

    <p>Merci pour votre confiance !</p>

    <p style="margin-top: 30px;">
        <strong>L'équipe RACINE BY GANDA</strong><br>
        <a href="mailto:contact@racinebyganda.com">contact@racinebyganda.com</a><br>
        <a href="{{ route('frontend.home') }}">www.racinebyganda.com</a>
    </p>
</body>
</html>

