<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }} - RACINE BY GANDA</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ED5F1E;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #ED5F1E;
            margin-bottom: 10px;
        }
        .message-content {
            background: #f8f9fa;
            border-left: 4px solid #ED5F1E;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .sender-info {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">RACINE BY GANDA</div>
            <h1 style="margin: 10px 0; color: #1A1A1A;">{{ $subject }}</h1>
        </div>

        <div class="sender-info">
            <strong>{{ $sender->name }}</strong>
            @if($sender->professional_email)
                <br><small style="color: #666;">{{ $sender->professional_email }}</small>
            @endif
        </div>

        <div class="message-content">{{ $content }}</div>

        <div style="text-align: center;">
            <a href="{{ $conversationUrl }}" class="button">Répondre dans la messagerie</a>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé depuis la messagerie RACINE BY GANDA.</p>
            <p>RACINE BY GANDA - Mode Africaine Authentique</p>
            <p style="margin-top: 10px;">
                <a href="{{ route('messages.index') }}" style="color: #ED5F1E; text-decoration: none;">Gérer mes conversations</a>
            </p>
        </div>
    </div>
</body>
</html>

