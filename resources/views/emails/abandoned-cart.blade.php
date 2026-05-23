<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre panier RACINE BY GANDA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #160D0C;
            background: #F5F0EC;
        }
        .wrapper { max-width: 600px; margin: 0 auto; background: white; }

        /* HEADER */
        .header {
            background: #160D0C;
            padding: 32px 40px;
            text-align: center;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
            color: white;
            letter-spacing: 3px;
        }
        .brand span { color: #FFB800; }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, #160D0C 0%, #2C1810 100%);
            padding: 40px 40px 32px;
            text-align: center;
        }
        .hero-emoji { font-size: 48px; margin-bottom: 16px; }
        .hero-title {
            font-size: 22px;
            font-weight: bold;
            color: white;
            margin-bottom: 8px;
        }
        .hero-sub { color: rgba(255,255,255,0.7); font-size: 14px; }

        /* BODY */
        .body { padding: 36px 40px; }

        .greeting { font-size: 16px; margin-bottom: 20px; }
        .greeting strong { color: #ED5F1E; }

        /* ITEMS */
        .items-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8B7355;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #F5F0EC;
        }
        .item-row {
            display: table;
            width: 100%;
            padding: 12px 0;
            border-bottom: 1px solid #F5F0EC;
        }
        .item-info { display: table-cell; vertical-align: middle; }
        .item-price {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-weight: bold;
            color: #ED5F1E;
            white-space: nowrap;
            padding-left: 12px;
        }
        .item-name { font-weight: bold; font-size: 14px; }
        .item-qty  { font-size: 12px; color: #8B7355; margin-top: 2px; }

        .total-row {
            display: table;
            width: 100%;
            margin-top: 16px;
            padding: 14px 16px;
            background: #F5F0EC;
            border-radius: 8px;
        }
        .total-label {
            display: table-cell;
            font-weight: bold;
            font-size: 15px;
        }
        .total-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            font-size: 17px;
            color: #ED5F1E;
        }

        /* CTA */
        .cta-section { text-align: center; margin: 32px 0 24px; }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
            color: white !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* URGENCY (reminder 3 only) */
        .urgency-box {
            background: #FEF3C7;
            border-left: 4px solid #FFB800;
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #92400E;
        }

        .divider {
            border: none;
            border-top: 1px solid #F5F0EC;
            margin: 24px 0;
        }

        .help-text {
            font-size: 12px;
            color: #8B7355;
            text-align: center;
        }

        /* FOOTER */
        .footer {
            background: #160D0C;
            padding: 24px 40px;
            text-align: center;
        }
        .footer p { color: rgba(255,255,255,0.5); font-size: 11px; line-height: 1.8; }
        .footer a { color: #FFB800; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- HEADER --}}
    <div class="header">
        <div class="brand">RACINE <span>BY GANDA</span></div>
    </div>

    {{-- HERO --}}
    <div class="hero">
        <div class="hero-emoji">
            @if($reminderNumber === 1) 🛍️
            @elseif($reminderNumber === 2) ✨
            @else ⏰
            @endif
        </div>
        <div class="hero-title">
            @if($reminderNumber === 1)
                Vous avez oublié quelque chose !
            @elseif($reminderNumber === 2)
                Votre panier vous attend toujours
            @else
                Dernière chance de finaliser votre commande
            @endif
        </div>
        <div class="hero-sub">RACINE BY GANDA — Mode africaine de luxe</div>
    </div>

    {{-- BODY --}}
    <div class="body">

        <p class="greeting">
            Bonjour <strong>{{ $user->name }}</strong>,
        </p>

        @if($reminderNumber === 1)
            <p style="margin-bottom:20px;">Vous avez ajouté des articles dans votre panier mais vous n'avez pas encore finalisé votre commande. Ne les laissez pas s'échapper !</p>
        @elseif($reminderNumber === 2)
            <p style="margin-bottom:20px;">Cela fait un moment que vous n'êtes pas revenu(e). Vos articles vous attendent toujours — mais les stocks sont limités !</p>
        @else
            <p style="margin-bottom:20px;">Votre panier va bientôt être vidé automatiquement. C'est peut-être votre dernière chance de commander ces pièces exclusives.</p>
        @endif

        @if($reminderNumber === 3)
        <div class="urgency-box">
            ⚠️ <strong>Attention :</strong> Les articles à stock limité peuvent être épuisés à tout moment. Commandez maintenant pour ne pas manquer votre pièce.
        </div>
        @endif

        {{-- ITEMS --}}
        <div class="items-title">Vos articles</div>

        @php $cartTotal = 0; @endphp
        @foreach($items as $item)
        @php
            $lineTotal = ($item->price ?? 0) * $item->quantity;
            $cartTotal += $lineTotal;
        @endphp
        <div class="item-row">
            <div class="item-info">
                <div class="item-name">{{ $item->product->title ?? 'Article' }}</div>
                <div class="item-qty">Quantité : {{ $item->quantity }}</div>
            </div>
            <div class="item-price">{{ number_format($lineTotal, 0, ',', ' ') }} FCFA</div>
        </div>
        @endforeach

        <div class="total-row">
            <span class="total-label">Total estimé</span>
            <span class="total-value">{{ number_format($cartTotal, 0, ',', ' ') }} FCFA</span>
        </div>

        {{-- CTA --}}
        <div class="cta-section">
            <a href="{{ $cartUrl }}" class="cta-btn">
                Finaliser ma commande →
            </a>
        </div>

        <hr class="divider">

        <p class="help-text">
            Des questions ? Écrivez-nous à <a href="mailto:{{ config('app.company.email', 'contact@racinebyganda.com') }}" style="color:#ED5F1E;">{{ config('app.company.email', 'contact@racinebyganda.com') }}</a>
        </p>

    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p>
            © {{ date('Y') }} RACINE BY GANDA · République du Congo, Pointe-Noire<br>
            <a href="{{ $shopUrl }}">Voir la boutique</a> ·
            Vous recevez cet email car vous avez un compte actif chez nous.
        </p>
    </div>

</div>
</body>
</html>
