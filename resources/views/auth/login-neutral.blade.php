<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion - RACINE BY GANDA</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <style nonce="{{ csp_nonce() }}">
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            background: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
            overflow-y: auto;
            position: relative;
        }

        .gradient-mesh {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(139,90,43,0.15), transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(212,165,116,0.1), transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255,107,0,0.05), transparent 70%);
        }

        .noise {
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: 0.03;
        }

        .container {
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            z-index: 10;
        }

        .login-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 2.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            color: #fff;
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.6);
            margin-top: .5rem;
        }

        .form-group { margin-bottom: 1.2rem; }

        .form-label {
            display: block;
            color: rgba(255,255,255,0.8);
            font-size: .85rem;
            margin-bottom: .4rem;
        }

        .form-control {
            width: 100%;
            padding: .85rem 1rem;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            outline: none;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .85rem;
            margin-bottom: 1.5rem;
        }

        .form-check {
            display: flex;
            gap: .5rem;
            align-items: center;
        }

        .form-check-label {
            color: rgba(255,255,255,0.7);
        }

        .forgot-link {
            color: rgba(212,165,116,0.8);
            text-decoration: none;
        }

        .btn-login {
            width: 100%;
            padding: .9rem;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg,#D4A574,#FF6B00);
            font-weight: 600;
            cursor: pointer;
        }

        .social-login {
            margin-top: 1.5rem;
        }

        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .7rem;
            width: 100%;
            padding: .85rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: #fff;
            text-decoration: none;
            margin-bottom: .75rem;
            transition: .3s;
        }

        .btn-social:hover {
            transform: translateY(-2px);
        }

        .btn-apple {
            background: rgba(0,0,0,0.35);
            border-color: rgba(255,255,255,0.2);
        }

        .btn-facebook {
            background: rgba(24,119,242,0.2);
            border-color: rgba(24,119,242,0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.6);
        }

        .login-footer a {
            color: #D4A574;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="gradient-mesh"></div>
<div class="noise"></div>

<div class="container">

    {{-- NAV --}}
    <div style="display:flex;gap:1rem;margin-bottom:1.5rem;">
        <a href="{{ url('/') }}" class="btn-social">← Retour</a>
        <a href="{{ route('frontend.home') }}" class="btn-social">🏠 Accueil</a>
    </div>

    <div class="login-card">

        {{-- HEADER --}}
        <div class="login-header">
            <h1 class="login-title">Connexion à votre compte</h1>
            <p class="login-subtitle">Accédez à votre espace RACINE BY GANDA</p>
        </div>

        {{-- ERRORS --}}
        @if($errors->any())
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:1rem;margin-bottom:1.5rem;color:#fecaca;font-size:.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('error'))
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:1rem;margin-bottom:1.5rem;color:#fecaca;font-size:.9rem;">
                {{ session('error') }}
            </div>
        @endif

        @if(session('status'))
            <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:12px;padding:1rem;margin-bottom:1.5rem;color:#bbf7d0;font-size:.9rem;">
                {{ session('status') }}
            </div>
        @endif

        {{-- FORM --}}
        <form method="POST" action="{{ route('login.post') }}" id="login-form">
            @csrf
            <input type="hidden" name="g-recaptcha-response" id="recaptcha-token">

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email"
                       class="form-control"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       aria-label="Adresse email"
                       aria-describedby="email-hint"
                       aria-required="true"
                       required>
                <small id="email-hint" class="text-muted" style="font-size:0.75rem;color:rgba(255,255,255,0.5);">Votre adresse email</small>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input id="password"
                       class="form-control"
                       type="password"
                       name="password"
                       aria-label="Mot de passe"
                       aria-describedby="password-hint"
                       aria-required="true"
                       required>
                <small id="password-hint" class="text-muted" style="font-size:0.75rem;color:rgba(255,255,255,0.5);">Votre mot de passe</small>
            </div>

            <div class="form-options">
                <label class="form-check">
                    <input type="checkbox" name="remember">
                    <span class="form-check-label">Se souvenir de moi</span>
                </label>

                <a class="forgot-link" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            </div>

            <button class="btn-login" type="submit">
                Se connecter
            </button>
        </form>

        {{-- SOCIAL LOGIN --}}
        <div class="social-login">

            @if(config('services.google.client_id'))
            <a class="btn-social"
               href="{{ route('auth.social.redirect', ['provider'=>'google','role'=>'client']) }}?context=boutique">
                <i class="fab fa-google"></i> Continuer avec Google
            </a>
            @endif

            @if(config('services.apple.client_id'))
            <a class="btn-social btn-apple"
               href="{{ route('auth.social.redirect', ['provider'=>'apple','role'=>'client']) }}?context=boutique">
                <i class="fab fa-apple"></i> Continuer avec Apple
            </a>
            @endif

            @if(config('services.facebook.client_id'))
            <a class="btn-social btn-facebook"
               href="{{ route('auth.social.redirect', ['provider'=>'facebook','role'=>'client']) }}?context=boutique">
                <i class="fab fa-facebook-f"></i> Continuer avec Facebook
            </a>
            @endif

        </div>

        {{-- FOOTER --}}
        <div class="login-footer">
            Pas de compte ?
            <a href="{{ route('register') }}">Créer un compte</a>
        </div>

    </div> {{-- login-card --}}
</div> {{-- container --}}

@if(config('recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}" nonce="{{ csp_nonce() }}"></script>
<script nonce="{{ csp_nonce() }}">
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    var originalHTML = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:0.5rem;"></i>Connexion...';

    grecaptcha.ready(function() {
        grecaptcha.execute('{{ config('recaptcha.site_key') }}', {action: 'login'}).then(function(token) {
            document.getElementById('recaptcha-token').value = token;
            form.submit();
        }).catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    });
});
</script>
@else
<script nonce="{{ csp_nonce() }}">
document.getElementById('login-form').addEventListener('submit', function(e) {
    var btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:0.5rem;"></i>Connexion...';
});
</script>
@endif

</body>
</html>