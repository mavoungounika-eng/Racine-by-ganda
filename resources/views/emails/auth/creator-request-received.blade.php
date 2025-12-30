<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de compte créateur reçue</title>
    <style>
        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #D4A574;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #8B5A2B;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .highlight-box {
            background: rgba(212, 165, 116, 0.1);
            border-left: 4px solid #D4A574;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .highlight-box strong {
            color: #8B5A2B;
            display: block;
            margin-bottom: 10px;
        }
        .highlight-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .highlight-box li {
            margin: 8px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">RACINE BY GANDA</div>
            <p style="color: #666; margin: 0;">Mode Africaine Premium</p>
        </div>

        <div class="content">
            <h1 style="color: #8B5A2B; font-size: 24px; margin-bottom: 20px;">
                Bonjour {{ $firstName }},
            </h1>

            <p style="font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                Votre demande de compte créateur a bien été reçue.
            </p>

            <div class="highlight-box">
                <strong>👉 Important :</strong>
                <ul>
                    <li>Vous gardez votre compte client</li>
                    <li>Vous ne perdez aucune commande</li>
                    <li>Vous pouvez continuer à acheter</li>
                </ul>
            </div>

            <p style="font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                Votre demande est en cours de vérification par l'équipe RACINE. 
                Nous vous notifierons dès que votre compte créateur sera validé.
            </p>

            <p style="font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                En attendant, vous pouvez continuer à utiliser votre compte normalement :
            </p>

            <ul style="font-size: 16px; line-height: 2; margin-bottom: 20px;">
                <li>✅ Acheter des produits</li>
                <li>✅ Suivre vos commandes</li>
                <li>✅ Gérer votre profil</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ route('account.dashboard') }}" class="cta-button">
                    Accéder à mon compte
                </a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                <strong>RACINE BY GANDA</strong><br>
                Mode Africaine Premium
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Cet email a été envoyé à {{ $user->email }}
            </p>
        </div>
    </div>
</body>
</html>



