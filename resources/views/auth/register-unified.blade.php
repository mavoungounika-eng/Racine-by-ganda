<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('racine/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            background: #111111;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 2rem 0;
        }
        
        .gradient-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(139, 90, 43, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(212, 165, 116, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 107, 0, 0.05) 0%, transparent 70%);
            z-index: 0;
            pointer-events: none;
        }
        
        .noise {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            padding: 2rem;
            position: relative;
            z-index: 10;
            margin: 0 auto;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 2rem;
            font-weight: 400;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .register-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        .alert-reassuring {
            background: rgba(212, 165, 116, 0.15);
            border: 1px solid rgba(212, 165, 116, 0.3);
            border-left: 4px solid #D4A574;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .alert-reassuring strong {
            color: #D4A574;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .alert-reassuring p {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .register-block {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .register-block::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D4A574, #E5B27B);
            transform: scaleX(1);
        }
        
        .register-block h2 {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .register-block .info {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 0.85rem 1.25rem;
            color: #fff;
            font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .form-control:focus {
            outline: none;
            border-color: rgba(212, 165, 116, 0.5);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 0.9rem 1.5rem;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 40%, #FF6B00 100%);
            color: #111;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 1.5rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 165, 116, 0.3);
        }
        
        .oauth-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .oauth-divider {
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.875rem;
            margin: 1rem 0;
        }
        
        .btn-oauth {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.85rem 1.5rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 0.75rem;
        }
        
        .btn-oauth:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: #fff;
            text-decoration: none;
        }
        
        .btn-oauth i {
            font-size: 1.1rem;
        }
        
        .btn-oauth.google {
            border-color: rgba(66, 133, 244, 0.3);
            background: rgba(66, 133, 244, 0.1);
        }
        
        .btn-oauth.google:hover {
            background: rgba(66, 133, 244, 0.2);
            border-color: rgba(66, 133, 244, 0.5);
        }
        
        .btn-oauth.apple {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.3);
        }
        
        .btn-oauth.apple:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        
        .btn-oauth.facebook {
            border-color: rgba(24, 119, 242, 0.3);
            background: rgba(24, 119, 242, 0.1);
        }
        
        .btn-oauth.facebook:hover {
            background: rgba(24, 119, 242, 0.2);
            border-color: rgba(24, 119, 242, 0.5);
        }
        
        .register-footer {
            margin-top: 2rem;
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .register-footer p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
        }
        
        .register-footer a {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .register-footer a:hover {
            color: #D4A574;
        }
        
        .error-message {
            color: #ff6b6b;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .alert-error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #ff6b6b;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 1.5rem;
            }
            
            .register-block {
                padding: 2rem 1.5rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>
    <div class="noise"></div>
    
    <div class="container">
        <div class="register-header">
            <h1 class="register-title">Créer un compte</h1>
            <p class="register-subtitle">Rejoignez l'univers RACINE BY GANDA</p>
        </div>
        
        {{-- MESSAGE RASSURANT --}}
        <div class="alert-reassuring">
            <strong>Un seul compte suffit.</strong>
            <p>Vous pourrez acheter et vendre avec le même compte.</p>
        </div>
        
        @if ($errors->any())
            <div class="alert-error">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif
        
        @php
            // Déterminer le contexte : 'creator' ou 'client' (par défaut)
            $isCreatorContext = isset($registerContext) && $registerContext === 'creator';
        @endphp
        
        @if($isCreatorContext)
            {{-- FORMULAIRE CRÉATEUR UNIQUEMENT --}}
            <div class="register-block">
                <h2>Créer un compte créateur</h2>
                
                <p class="info">
                    Votre compte créateur sera validé par notre équipe.
                    Vous pourrez toujours acheter pendant ce temps.
                </p>
                
                <form method="POST" action="{{ route('register.post') }}">
                    @csrf
                    <input type="hidden" name="account_type" value="creator">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nom complet</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="Votre nom complet" 
                               required
                               autofocus
                               value="{{ old('name') }}">
                        @error('name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="exemple@email.com" 
                               required
                               autocomplete="email"
                               value="{{ old('email') }}">
                        @error('email')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="12 caractères min." 
                               required
                               autocomplete="new-password">
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmation</label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="form-control" 
                               placeholder="Répétez le mot de passe" 
                               required
                               autocomplete="new-password">
                    </div>
                    
                    <button type="submit" class="btn-register">
                        Créer mon compte créateur
                    </button>
                </form>
                
                <div class="oauth-section">
                    <div class="oauth-divider">ou</div>
                    <a href="{{ route('auth.social.redirect', ['provider' => 'google', 'role' => 'creator']) }}" class="btn-oauth google">
                        <i class="fab fa-google"></i>
                        <span>Google</span>
                    </a>
                    <a href="{{ route('auth.social.redirect', ['provider' => 'apple', 'role' => 'creator']) }}" class="btn-oauth apple">
                        <i class="fab fa-apple"></i>
                        <span>Apple</span>
                    </a>
                    <a href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'role' => 'creator']) }}" class="btn-oauth facebook">
                        <i class="fab fa-facebook-f"></i>
                        <span>Facebook</span>
                    </a>
                </div>
                
                <div class="register-footer">
                    <p>
                        Vous souhaitez simplement acheter ? 
                        <a href="{{ route('register', ['context' => 'boutique']) }}">Créer un compte client</a>
                    </p>
                    <p style="margin-top: 0.5rem;">
                        Déjà un compte ? 
                        <a href="{{ route('creator.login') }}">Se connecter</a>
                    </p>
                </div>
            </div>
        @else
            {{-- FORMULAIRE CLIENT UNIQUEMENT --}}
            <div class="register-block">
                <h2>Créer un compte client</h2>
                
                <form method="POST" action="{{ route('register.post') }}">
                    @csrf
                    <input type="hidden" name="account_type" value="client">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nom complet</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="Votre nom complet" 
                               required
                               autofocus
                               value="{{ old('name') }}">
                        @error('name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="exemple@email.com" 
                               required
                               autocomplete="email"
                               value="{{ old('email') }}">
                        @error('email')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="12 caractères min." 
                               required
                               autocomplete="new-password">
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmation</label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="form-control" 
                               placeholder="Répétez le mot de passe" 
                               required
                               autocomplete="new-password">
                    </div>
                    
                    <button type="submit" class="btn-register">
                        Créer mon compte client
                    </button>
                </form>
                
                <div class="oauth-section">
                    <div class="oauth-divider">ou</div>
                    <a href="{{ route('auth.social.redirect', ['provider' => 'google', 'role' => 'client']) }}" class="btn-oauth google">
                        <i class="fab fa-google"></i>
                        <span>Google</span>
                    </a>
                    <a href="{{ route('auth.social.redirect', ['provider' => 'apple', 'role' => 'client']) }}" class="btn-oauth apple">
                        <i class="fab fa-apple"></i>
                        <span>Apple</span>
                    </a>
                    <a href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'role' => 'client']) }}" class="btn-oauth facebook">
                        <i class="fab fa-facebook-f"></i>
                        <span>Facebook</span>
                    </a>
                </div>
                
                <div class="register-footer">
                    <p>
                        Vous souhaitez vendre vos créations ? 
                        <a href="{{ route('creator.register') }}">Créer un compte créateur</a>
                    </p>
                    <p style="margin-top: 0.5rem;">
                        Déjà un compte ? 
                        <a href="{{ route('login', ['context' => 'boutique']) }}">Se connecter</a>
                    </p>
                </div>
            </div>
        @endif
    </div>
</body>
</html>

