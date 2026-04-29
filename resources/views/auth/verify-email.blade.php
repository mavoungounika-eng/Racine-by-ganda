<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style nonce="{{ csp_nonce() }}">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; min-height: 100vh; background: #111; display: flex; align-items: center; justify-content: center; padding: 2rem 0; }
        .gradient-mesh { position: fixed; inset: 0; background: radial-gradient(circle at 20% 30%, rgba(139,90,43,0.15), transparent 50%), radial-gradient(circle at 80% 70%, rgba(212,165,116,0.1), transparent 50%); }
        .container { width: 100%; max-width: 520px; padding: 2rem; z-index: 10; }
        .card { background: rgba(255,255,255,0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 2.5rem; text-align: center; }
        .icon { width: 72px; height: 72px; background: rgba(212,165,116,0.15); border: 1px solid rgba(212,165,116,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: #D4A574; }
        h1 { font-family: 'Libre Baskerville', serif; font-size: 1.5rem; color: #fff; margin-bottom: 0.75rem; }
        .subtitle { color: rgba(255,255,255,0.6); font-size: 0.9rem; line-height: 1.6; margin-bottom: 2rem; }
        .email-highlight { color: #D4A574; font-weight: 500; }
        .success { background: rgba(72,199,142,0.1); border: 1px solid rgba(72,199,142,0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #48c78e; font-size: 0.875rem; }
        .btn { width: 100%; padding: 0.9rem; border-radius: 999px; border: none; background: linear-gradient(135deg, #D4A574, #FF6B00); color: #111; font-weight: 600; font-size: 1rem; cursor: pointer; font-family: 'Outfit', sans-serif; transition: 0.3s; margin-bottom: 1rem; }
        .btn:hover { transform: translateY(-2px); }
        .btn-outline { width: 100%; padding: 0.9rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.15); background: transparent; color: rgba(255,255,255,0.7); font-weight: 500; font-size: 0.95rem; cursor: pointer; font-family: 'Outfit', sans-serif; transition: 0.3s; }
        .btn-outline:hover { border-color: rgba(255,255,255,0.3); color: #fff; }
        .divider { color: rgba(255,255,255,0.3); font-size: 0.85rem; margin: 1rem 0; }
        .logout-btn { background: none; border: none; color: rgba(255,255,255,0.4); font-size: 0.85rem; cursor: pointer; font-family: 'Outfit', sans-serif; margin-top: 1rem; display: block; width: 100%; }
        .logout-btn:hover { color: rgba(255,255,255,0.7); }
    </style>
</head>
<body>
<div class="gradient-mesh"></div>
<div class="container">
    <div class="card">
        <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
        <h1>Vérifiez votre email</h1>
        <p class="subtitle">
            Un lien de vérification a été envoyé à<br>
            <span class="email-highlight">{{ Auth::user()->email }}</span><br><br>
            Cliquez sur le lien dans l'email pour activer votre compte.
        </p>
        @if(session('status') === 'verification-link-sent')
            <div class="success">
                <i class="fas fa-check-circle"></i> Un nouvel email de vérification a été envoyé.
            </div>
        @endif
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Renvoyer l'email
            </button>
        </form>
        <div class="divider">ou</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Se déconnecter</button>
        </form>
    </div>
</div>
</body>
</html>
