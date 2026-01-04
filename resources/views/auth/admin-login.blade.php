<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Équipe - RACINE BY GANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {!! NoCaptcha::renderJs() !!}
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-y: auto;
            padding: 2rem 0;
        }
        
        /* GRID PATTERN */
        .grid-pattern {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }
        
        .container {
            width: 100%;
            max-width: 440px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        
        /* SECURITY BADGE */
        .security-badge {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .security-badge i {
            font-size: 1.25rem;
            color: #ef4444;
        }
        
        .security-badge-content h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fecaca;
            margin-bottom: 0.25rem;
        }
        
        .security-badge-content p {
            font-size: 0.75rem;
            color: rgba(254, 202, 202, 0.8);
            line-height: 1.4;
        }
        
        /* LOGIN CARD */
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .login-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* FORM */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            font-size: 0.9375rem;
            color: white;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-checkbox input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .form-checkbox label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
        }
        
        /* BUTTON */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* LINKS */
        .form-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .form-links a {
            font-size: 0.875rem;
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .form-links a:hover {
            color: #93c5fd;
        }
        
        /* ALERT */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fecaca;
        }
        
        /* FOOTER */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-footer p {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.6;
        }
        
        .login-footer i {
            color: #ef4444;
            margin-right: 0.25rem;
        }
        
        /* CAPTCHA */
        .captcha-container {
            margin: 1.5rem 0;
        }
        
        /* RESPONSIVE */
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="grid-pattern"></div>
    
    <div class="container">
        <!-- SECURITY BADGE -->
        <div class="security-badge">
            <i class="fas fa-shield-halved"></i>
            <div class="security-badge-content">
                <h4>Espace Réservé au Personnel</h4>
                <p>Toutes les connexions sont surveillées et enregistrées</p>
            </div>
        </div>
        
        <!-- LOGIN CARD -->
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h1>Connexion Équipe</h1>
                <p>Accès au système de gestion interne</p>
            </div>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email professionnelle</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="votre.email@racine-by-ganda.com"
                        value="{{ old('email') }}"
                        required 
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Se souvenir de moi</label>
                    </div>
                </div>
                
                @if(session('show_captcha') || old('show_captcha'))
                    <div class="captcha-container">
                        {!! NoCaptcha::display() !!}
                    </div>
                @endif
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                    Se connecter
                </button>
            </form>
            
            <div class="form-links">
                <a href="{{ route('password.request') }}">
                    <i class="fas fa-key"></i> Mot de passe oublié ?
                </a>
            </div>
            
            <div class="login-footer">
                <p>
                    <i class="fas fa-exclamation-triangle"></i>
                    Accès strictement réservé aux membres autorisés de l'équipe RACINE BY GANDA.<br>
                    Toute tentative d'accès non autorisée sera signalée et poursuivie.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
