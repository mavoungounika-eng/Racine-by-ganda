<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* GRADIENT MESH */
        .gradient-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(139, 90, 43, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(212, 165, 116, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 107, 0, 0.05) 0%, transparent 70%);
        }
        
        /* NOISE TEXTURE */
        .noise {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
        }
        
        .container {
            width: 100%;
            max-width: 550px;
            padding: 2rem;
            position: relative;
            z-index: 10;
            margin: 0 auto;
        }
        
        /* BOUTON RETOUR */
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
        
        .auth-back-link i {
            font-size: 0.9rem;
        }
        
        .auth-back-link:hover {
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* CARTE PRINCIPALE */
        .register-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent, #D4A574), var(--accent-light, #E5B27B));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s;
        }
        
        .register-card:hover::before {
            transform: scaleX(1);
        }
        
        .register-card.boutique {
            --accent: #D4A574;
            --accent-light: #E5B27B;
        }
        
        .register-card.equipe {
            --accent: #FF6B00;
            --accent-light: #FFB800;
        }
        
        /* HEADER REGISTER */
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-badge {
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
        
        .register-card.equipe .register-badge {
            background: rgba(255, 107, 0, 0.15);
            color: #FF6B00;
            border-color: rgba(255, 107, 0, 0.3);
        }
        
        .register-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .register-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        /* FORMULAIRE */
        .register-form {
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .account-type-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .account-type-option {
            position: relative;
        }
        
        .account-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .account-type-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .account-type-option input[type="radio"]:checked + label {
            background: rgba(212, 165, 116, 0.15);
            border-color: #D4A574;
            color: #D4A574;
        }
        
        .account-type-option label:hover {
            border-color: rgba(212, 165, 116, 0.5);
        }
        
        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0.2rem;
            cursor: pointer;
            accent-color: #D4A574;
            flex-shrink: 0;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            line-height: 1.5;
            cursor: pointer;
            user-select: none;
        }
        
        .form-check-label a {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .form-check-label a:hover {
            color: #D4A574;
        }
        
        /* BOUTON REGISTER */
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
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 165, 116, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .register-card.equipe .btn-register {
            background: linear-gradient(135deg, #FF6B00 0%, #FFB800 100%);
        }
        
        .register-card.equipe .btn-register:hover {
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.3);
        }
        
        /* BOUTON GOOGLE */
        .social-login {
            margin-bottom: 1.5rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.875rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .divider span {
            padding: 0 1rem;
        }
        
        .btn-social-google {
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
        }
        
        .btn-social-google:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-social-google i {
            font-size: 1.1rem;
        }
        
        /* LIENS FOOTER */
        .register-footer {
            margin-top: 2rem;
            text-align: center;
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
        
        /* ERREURS */
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
        
        .alert-error ul {
            margin: 0;
            padding-left: 1.25rem;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 1.5rem;
            }
            
            .register-card {
                padding: 2rem 1.5rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
            
            .register-subtitle {
                font-size: 0.875rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .account-type-group {
                grid-template-columns: 1fr;
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
        
        <div class="register-card {{ $registerContext ?? '' }}">
            @php
                // Résoudre le contexte (boutique, equipe ou neutral)
                $context = $registerContext ?? 'neutral';
                
                // Définir les textes selon le contexte
                $title = 'Créer votre compte';
                $subtitle = 'Rejoignez l\'univers RACINE BY GANDA et suivez vos commandes en toute simplicité.';
                $badge = null;
                $icon = null;
                
                if ($context === 'boutique') {
                    $title = 'Inscription – Espace Boutique';
                    $subtitle = 'Clients et créateurs, créez votre compte pour accéder à vos commandes, favoris et suivis.';
                    $badge = 'Boutique';
                    $icon = 'fa-shopping-bag';
                } elseif ($context === 'equipe') {
                    $title = 'Inscription – Espace Équipe';
                    $subtitle = 'Membres de l\'équipe, créez votre accès à l\'espace de gestion (réservé).';
                    $badge = 'Équipe';
                    $icon = 'fa-briefcase';
                }
            @endphp
            
            <div class="register-header">
                @if($badge)
                    <span class="register-badge">
                        @if($icon)
                            <i class="fas {{ $icon }}"></i>
                        @endif
                        {{ $badge }}
                    </span>
                @endif
                
                <h1 class="register-title">{{ $title }}</h1>
                <p class="register-subtitle">{{ $subtitle }}</p>
            </div>
            
            @if ($errors->any())
                <div class="alert-error">
                    <ul>
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
            
            @if($context === 'boutique')
            <div class="social-login">
                <a href="{{ route('auth.google.redirect', ['context' => 'boutique']) }}" 
                   class="btn-social-google">
                    <i class="fab fa-google"></i>
                    <span>S'inscrire avec Google</span>
                </a>
            </div>
            
            <div class="divider">
                <span>ou</span>
            </div>
            @endif
            
            <form method="POST" action="{{ route('register.post') }}" class="register-form">
                @csrf
                
                <div class="form-group">
                    <label for="name" class="form-label">Nom complet</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        placeholder="Votre nom complet" 
                        required
                        autofocus
                        value="{{ old('name') }}"
                    >
                    @error('name')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="exemple@email.com" 
                        required
                        autocomplete="email"
                        value="{{ old('email') }}"
                    >
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="8 caractères min." 
                            required
                            autocomplete="new-password"
                        >
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmation</label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="form-control" 
                            placeholder="Répétez le mot de passe" 
                            required
                            autocomplete="new-password"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type de compte</label>
                    <div class="account-type-group">
                        <div class="account-type-option">
                            <input 
                                type="radio" 
                                id="account_type_client" 
                                name="account_type" 
                                value="client" 
                                checked
                            >
                            <label for="account_type_client">
                                <i class="fas fa-shopping-bag"></i>
                                Client
                            </label>
                        </div>
                        <div class="account-type-option">
                            <input 
                                type="radio" 
                                id="account_type_creator" 
                                name="account_type" 
                                value="creator"
                            >
                            <label for="account_type_creator">
                                <i class="fas fa-palette"></i>
                                Créateur
                            </label>
                        </div>
                    </div>
                    @error('account_type')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        class="form-check-input" 
                        id="terms" 
                        name="terms" 
                        required
                    >
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="{{ route('frontend.terms') }}" target="_blank">conditions d'utilisation</a> et la <a href="{{ route('frontend.privacy') }}" target="_blank">politique de confidentialité</a>
                    </label>
                </div>
                @error('terms')
                    <span class="error-message">{{ $message }}</span>
                @enderror
                
                <button type="submit" class="btn-register">
                    Créer mon compte
                </button>
            </form>
            
            <div class="register-footer">
                <p>Vous avez déjà un compte ? <a href="{{ route('login', ['context' => $context !== 'neutral' ? $context : null]) }}">Se connecter</a></p>
            </div>
            
            {{-- DISTINCTION CLIENT / CRÉATEUR --}}
            <div class="creator-link-section" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: rgba(255, 255, 255, 0.5); font-size: 0.875rem; margin-bottom: 0.75rem;">
                    Vous souhaitez vendre vos créations avec RACINE BY GANDA ?
                </p>
                <a href="{{ route('creator.register') }}" 
                   style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 999px; border: 1px solid rgba(34, 197, 94, 0.3); background: rgba(34, 197, 94, 0.1); color: #22c55e; font-weight: 500; font-size: 0.9rem; text-decoration: none; transition: all 0.3s;"
                   onmouseover="this.style.background='rgba(34, 197, 94, 0.2)'; this.style.borderColor='rgba(34, 197, 94, 0.5)'; this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.background='rgba(34, 197, 94, 0.1)'; this.style.borderColor='rgba(34, 197, 94, 0.3)'; this.style.transform='translateY(0)'">
                    <i class="fas fa-palette"></i>
                    Devenir créateur partenaire
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
</body>
</html>
