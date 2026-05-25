<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #160D0C; background: #F5F0EC; }
        .wrapper { max-width: 600px; margin: 0 auto; background: white; }
        .header { background: #160D0C; padding: 28px 40px; text-align: center; }
        .brand { font-size: 22px; font-weight: bold; color: white; letter-spacing: 3px; }
        .brand span { color: #FFB800; }
        .hero { background: linear-gradient(135deg, #160D0C 0%, #2C1810 100%); padding: 32px 40px 28px; text-align: center; }
        .hero-title { font-size: 20px; font-weight: bold; color: white; margin-bottom: 6px; }
        .hero-sub { color: rgba(255,255,255,0.7); font-size: 14px; }
        .body { padding: 32px 40px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .greeting strong { color: #ED5F1E; }
        .message-box { background: #F5F0EC; border-left: 4px solid #ED5F1E; border-radius: 4px; padding: 16px 20px; margin: 20px 0; font-size: 14px; color: #160D0C; line-height: 1.7; }
        .order-ref { font-size: 13px; color: #8B7355; margin-bottom: 24px; }
        .cta { text-align: center; margin: 28px 0; }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, #ED5F1E 0%, #d45519 100%);
            color: white !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
            padding: 14px 32px;
            border-radius: 8px;
            letter-spacing: 0.5px;
        }
        .footer { background: #F5F0EC; padding: 24px 40px; text-align: center; color: #8B7355; font-size: 12px; border-top: 1px solid #e8e0d8; }
        .footer a { color: #ED5F1E; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">

        <div class="header">
            <div class="brand">RACINE <span>BY GANDA</span></div>
        </div>

        <div class="hero">
            <div class="hero-title">{{ $subject }}</div>
            <div class="hero-sub">Commande #{{ $order->id }}</div>
        </div>

        <div class="body">
            <p class="greeting">Bonjour <strong>{{ $notifiable->name }}</strong>,</p>

            <div class="message-box">
                {{ $bodyMessage }}
            </div>

            <p class="order-ref">
                Article concerné : <strong>{{ $item->product?->title ?? 'Produit #'.$item->product_id }}</strong><br>
                Commande : <strong>#{{ $order->id }}</strong> — {{ $order->created_at->format('d/m/Y') }}
            </p>

            <div class="cta">
                <a href="{{ route('profile.orders.show', $order->id) }}" class="cta-btn">
                    Voir ma commande
                </a>
            </div>

            <p style="color:#8B7355;font-size:13px;margin-top:20px;">
                Si vous avez des questions, n'hésitez pas à contacter notre support depuis votre espace client.
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} RACINE BY GANDA — Boutique de Mode Africaine</p>
            <p style="margin-top:6px;">
                <a href="{{ route('profile.orders.show', $order->id) }}">Voir ma commande</a>
            </p>
        </div>

    </div>
</body>
</html>
