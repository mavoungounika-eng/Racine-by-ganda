<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - RACINE BY GANDA</title>
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
            max-width: 500px;
            padding: 2rem;
            position: relative;
            z-index: 10;
            margin: 0 auto;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            margin-bottom: 1.5rem;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .forgot-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .forgot-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D4A574, #E5B27B);
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .forgot-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            background: rgba(212, 165, 116, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #D4A574;
            font-size: 1.5rem;
        }
        
        .forgot-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .forgot-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-left: 4px solid #4CAF50;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #4CAF50;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-left: 4px solid #ff6b6b;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #ff6b6b;
            font-size: 0.875rem;
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
        
        .btn-submit {
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
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 165, 116, 0.3);
        }
        
        .forgot-footer {
            margin-top: 2rem;
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .forgot-footer p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
        }
        
        .forgot-footer a {
            color: rgba(212, 165, 116, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .forgot-footer a:hover {
            color: #D4A574;
        }
        
        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 1.5rem;
            }
            
            .forgot-card {
                padding: 2rem 1.5rem;
            }
            
            .forgot-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>
    <div class="noise"></div>
    
    <div class="container">
        <a href="{{ route('login') }}" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Retour à la connexion</span>
        </a>
        
        <div class="forgot-card">
            <div class="forgot-header">
                <div class="forgot-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="forgot-title">Mot de passe oublié ?</h1>
                <p class="forgot-subtitle">
                    Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
                </p>
            </div>
            
            @if (session('status'))
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    {{ session('status') }}
                </div>
            @endif
            
            @if ($errors->any())
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif
            
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="votre@email.com" 
                           required
                           autofocus
                           value="{{ old('email') }}">
                </div>
                
                <button type="submit" class="btn-submit">
                    Envoyer le lien de réinitialisation
                </button>
            </form>
            
            <div class="forgot-footer">
                <p>
                    Vous vous souvenez de votre mot de passe ? 
                    <a href="{{ route('login') }}">Se connecter</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
