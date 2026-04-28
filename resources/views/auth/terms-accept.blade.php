<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions d'utilisation - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style nonce="{{ csp_nonce() }}">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; min-height: 100vh; background: #111; display: flex; align-items: center; justify-content: center; padding: 2rem 0; }
        .gradient-mesh { position: fixed; inset: 0; background: radial-gradient(circle at 20% 30%, rgba(139,90,43,0.15), transparent 50%), radial-gradient(circle at 80% 70%, rgba(212,165,116,0.1), transparent 50%); }
        .container { width: 100%; max-width: 560px; padding: 2rem; z-index: 10; }
        .card { background: rgba(255,255,255,0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 2.5rem; }
        .header { text-align: center; margin-bottom: 2rem; }
        .icon { width: 56px; height: 56px; background: rgba(212,165,116,0.15); border: 1px solid rgba(212,165,116,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: #D4A574; }
        h1 { font-family: 'Libre Baskerville', serif; font-size: 1.5rem; color: #fff; margin-bottom: 0.5rem; }
        .subtitle { color: rgba(255,255,255,0.6); font-size: 0.9rem; }
        .terms-box { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; max-height: 200px; overflow-y: auto; color: rgba(255,255,255,0.7); font-size: 0.875rem; line-height: 1.7; }
        .terms-box h3 { color: #D4A574; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .terms-box p { margin-bottom: 0.75rem; }
        .check-group { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1.5rem; padding: 1rem; background: rgba(212,165,116,0.05); border: 1px solid rgba(212,165,116,0.15); border-radius: 12px; }
        .check-group input[type="checkbox"] { width: 20px; height: 20px; accent-color: #D4A574; margin-top: 2px; flex-shrink: 0; cursor: pointer; }
        .check-group label { color: rgba(255,255,255,0.8); font-size: 0.9rem; line-height: 1.5; cursor: pointer; }
        .check-group label a { color: #D4A574; text-decoration: none; }
        .error { color: #ff6b6b; font-size: 0.85rem; margin-bottom: 1rem; padding: 0.75rem; background: rgba(255,107,107,0.1); border-radius: 8px; border: 1px solid rgba(255,107,107,0.2); }
        .btn { width: 100%; padding: 0.9rem; border-radius: 999px; border: none; background: linear-gradient(135deg, #D4A574, #FF6B00); color: #111; font-weight: 600; font-size: 1rem; cursor: pointer; font-family: 'Outfit', sans-serif; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); }
        .logout-btn { display: block; text-align: center; margin-top: 1rem; color: rgba(255,255,255,0.4); font-size: 0.85rem; background: none; border: none; width: 100%; cursor: pointer; font-family: 'Outfit', sans-serif; }
        .logout-btn:hover { color: rgba(255,255,255,0.7); }
    </style>
</head>
<body>
<div class="gradient-mesh"></div>
<div class="container">
    <div class="card">
        <div class="header">
            <div class="icon"><i class="fas fa-file-contract"></i></div>
            <h1>Conditions d'utilisation</h1>
            <p class="subtitle">Veuillez lire et accepter nos conditions avant de continuer.</p>
        </div>
        <div class="terms-box">
            <h3>1. Utilisation de la plateforme</h3>
            <p>En utilisant RACINE BY GANDA, vous acceptez de respecter nos règles de communauté et de ne pas utiliser la plateforme à des fins illicites.</p>
            <h3>2. Données personnelles</h3>
            <p>Vos données sont traitées conformément à notre politique de confidentialité. Nous ne vendons jamais vos données à des tiers.</p>
            <h3>3. Propriété intellectuelle</h3>
            <p>Les créations présentées sur la plateforme restent la propriété de leurs créateurs respectifs.</p>
            <h3>4. Responsabilité</h3>
            <p>RACINE BY GANDA agit en tant qu'intermédiaire entre acheteurs et créateurs et ne peut être tenu responsable des litiges entre parties.</p>
        </div>
        @if($errors->any())
            <div class="error">{{ $errors->first('terms') }}</div>
        @endif
        <form method="POST" action="{{ route('terms.accept.post') }}">
            @csrf
            <div class="check-group">
                <input type="checkbox" id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }}>
                <label for="terms">
                    J'ai lu et j'accepte les <a href="{{ route('frontend.terms') }}" target="_blank">conditions d'utilisation</a>
                    et la <a href="{{ route('frontend.privacy') }}" target="_blank">politique de confidentialité</a> de RACINE BY GANDA.
                </label>
            </div>
            <button type="submit" class="btn">Accepter et continuer</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Se déconnecter</button>
        </form>
    </div>
</div>
</body>
</html>
