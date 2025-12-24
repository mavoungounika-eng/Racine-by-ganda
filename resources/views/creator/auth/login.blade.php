<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Créateur - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            background: #111111;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-y: auto;
            padding: 2rem 0;
            overflow-x: hidden;
        }
        
        /* Background motif animé */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .gradient-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(139, 90, 43, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(212, 165, 116, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 107, 0, 0.05) 0%, transparent 70%);
        }
        
        .noise {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
        }
        
        .container {
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        
        .auth-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            margin-bottom: 1.5rem;
            transition: color 0.3s;
        }
        
        .auth-back-link:hover {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D4A574, #E5B27B);
            transform: scaleX(1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            background: rgba(212, 165, 116, 0.15);
            color: #D4A574;
            border: 1px solid rgba(212, 165, 116, 0.3);
        }
        
        .login-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .login-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        .login-form {
            margin-top: 2rem;
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #D4A574;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
        }
        
        .forgot-link {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: #D4A574;
        }
        
        .btn-login {
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
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 165, 116, 0.3);
        }
        
        .login-footer {
            margin-top: 2rem;
            text-align: center;
        }
        
        .login-footer p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .login-footer a {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
            color: #D4A574;
        }
        
        .creator-link-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .creator-link-section p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        .btn-creator-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            border: 1px solid rgba(212, 165, 116, 0.3);
            background: rgba(212, 165, 116, 0.1);
            color: #D4A574;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-creator-link:hover {
            background: rgba(212, 165, 116, 0.2);
            border-color: rgba(212, 165, 116, 0.5);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 1.5rem;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>
    <div class="noise"></div>
    
    <div class="container">
        <a href="{{ route('auth.hub') }}" class="auth-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Retour au choix d'espace</span>
        </a>
        
        <div class="login-card">
            <div class="login-header">
                <span class="login-badge">
                    <i class="fas fa-palette"></i>
                    Espace Créateur
                </span>
                
                <h1 class="login-title">Connexion Créateur</h1>
                <p class="login-subtitle">Accédez à votre espace vendeur pour gérer vos produits, commandes et finances.</p>
            </div>
            
            {{-- MESSAGE INFORMATIF --}}
            <div style="background: rgba(212, 165, 116, 0.15); border: 1px solid rgba(212, 165, 116, 0.3); border-left: 4px solid #D4A574; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.9); font-size: 0.875rem;">
                <div style="display: flex; align-items: start; gap: 0.75rem;">
                    <i class="fas fa-info-circle" style="color: #D4A574; font-size: 1rem; margin-top: 0.1rem;"></i>
                    <div>
                        <strong style="color: #D4A574; display: block; margin-bottom: 0.25rem;">Un seul compte suffit.</strong>
                        <p style="margin: 0; font-size: 0.85rem; line-height: 1.5;">
                            Vous pouvez acheter et vendre avec le même compte, sans jamais perdre vos données.
                        </p>
                    </div>
                </div>
            </div>
            
            @if(session('status'))
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #22c55e; font-size: 0.875rem;">
                    {{ session('status') }}
                </div>
            @endif
            
            @if(session('error'))
                <div style="background: rgba(255, 107, 107, 0.1); border: 1px solid rgba(255, 107, 107, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #ff6b6b; font-size: 0.875rem;">
                    {{ session('error') }}
                </div>
            @endif
            
            @if(session('success'))
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #22c55e; font-size: 0.875rem;">
                    {{ session('success') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('creator.login.post') }}" class="login-form">
                @csrf
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="votre@email.com" 
                        required
                        autocomplete="email"
                        value="{{ old('email') }}"
                    >
                    @error('email')
                        <span style="color: #ff6b6b; font-size: 0.8rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de Passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••" 
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <span style="color: #ff6b6b; font-size: 0.8rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-options">
                    <div class="form-check">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            id="remember" 
                            name="remember"
                        >
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot-link">Mot de passe oublié ?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    Se Connecter
                </button>
            </form>
            
            {{-- CONNEXION SOCIALE --}}
            <div class="social-login" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <span style="color: rgba(255, 255, 255, 0.5); font-size: 0.875rem;">Ou continuer avec</span>
                </div>
                
                <a href="{{ route('auth.social.redirect', ['provider' => 'google', 'role' => 'creator']) }}" 
                   class="btn-social-google" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.85rem 1.5rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.15); background: rgba(255, 255, 255, 0.05); color: #fff; font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; font-family: 'Outfit', sans-serif; margin-bottom: 0.75rem;">
                    <i class="fab fa-google"></i>
                    <span>Continuer avec Google</span>
                </a>
                
                <a href="{{ route('auth.social.redirect', ['provider' => 'apple', 'role' => 'creator']) }}" 
                   class="btn-social-google" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.85rem 1.5rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0, 0, 0, 0.3); color: #fff; font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; font-family: 'Outfit', sans-serif; margin-bottom: 0.75rem;">
                    <i class="fab fa-apple"></i>
                    <span>Continuer avec Apple</span>
                </a>
                
                <a href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'role' => 'creator']) }}" 
                   class="btn-social-google" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.85rem 1.5rem; border-radius: 999px; border: 1px solid rgba(24, 119, 242, 0.4); background: rgba(24, 119, 242, 0.2); color: #fff; font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; font-family: 'Outfit', sans-serif;">
                    <i class="fab fa-facebook-f"></i>
                    <span>Continuer avec Facebook</span>
                </a>
            </div>
            
            <div class="login-footer">
                <p>Pas encore de compte créateur ? <a href="{{ route('creator.register') }}">Ouvrir un compte</a></p>
            </div>
            
            <div class="creator-link-section">
                <p>Vous êtes client ?</p>
                <a href="{{ route('login', ['context' => 'boutique']) }}" class="btn-creator-link">
                    <i class="fas fa-shopping-bag"></i>
                    Accéder à l'espace client
                </a>
            </div>
        </div>
    </div>
    
    {{-- BACKGROUND MOTIF ANIMÉ -- Désactivé --}}
    {{-- <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none;">
        @php
            echo view('components.racine-logo-animation', ['variant' => 'background', 'theme' => 'dark'])->render();
        @endphp
    </div> --}}
    
    <style>
        .login-card,
        .login-container {
            position: relative;
            z-index: 1;
        }
    </style>
</body>
</html>

