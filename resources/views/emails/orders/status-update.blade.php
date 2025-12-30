<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de commande</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #4F46E5;">RACINE BY GANDA</h1>
    </div>

    <h2 style="color: #4F46E5;">Mise à jour de votre commande</h2>
    
    <p>Bonjour {{ $order->customer_name }},</p>
    
    <p>Le statut de votre commande <strong>#{{ $order->id }}</strong> a été mis à jour.</p>

    <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p><strong>Nouveau statut :</strong> 
            @if($newStatus === 'processing')
                <span style="color: #f59e0b;">En préparation</span>
            @elseif($newStatus === 'shipped')
                <span style="color: #3b82f6;">Expédiée</span>
            @elseif($newStatus === 'completed')
                <span style="color: #10b981;">Livrée</span>
            @elseif($newStatus === 'cancelled')
                <span style="color: #ef4444;">Annulée</span>
            @else
                {{ ucfirst($newStatus) }}
            @endif
        </p>
        <p><strong>Montant :</strong> {{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</p>
    </div>

    @if($newStatus === 'shipped')
    <p>Votre commande a été expédiée et devrait arriver sous peu. Nous vous tiendrons informé(e) de sa livraison.</p>
    @elseif($newStatus === 'completed')
    <p>Votre commande a été livrée avec succès ! Nous espérons que vous serez satisfait(e) de votre achat.</p>
    @elseif($newStatus === 'cancelled')
    <p>Si vous avez des questions concernant cette annulation, n'hésitez pas à nous contacter.</p>
    @endif

    <p style="margin-top: 30px;">
        <strong>L'équipe RACINE BY GANDA</strong><br>
        <a href="mailto:contact@racinebyganda.com">contact@racinebyganda.com</a><br>
        <a href="{{ route('frontend.home') }}">www.racinebyganda.com</a>
    </p>
</body>
</html>

