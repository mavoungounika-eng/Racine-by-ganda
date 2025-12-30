<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Suspendu - RACINE BY GANDA</title>
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
            overflow-x: hidden;
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
            max-width: 500px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .status-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff6b6b, #dc2626);
        }
        
        .status-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: rgba(255, 107, 107, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #ff6b6b;
        }
        
        .status-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: white;
            margin-bottom: 1rem;
        }
        
        .status-message {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .status-info {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .status-info h4 {
            color: #ff6b6b;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .status-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.9rem 2rem;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 40%, #FF6B00 100%);
            color: #111;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 165, 116, 0.3);
        }
        
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            .status-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>
    <div class="noise"></div>
    
    <div class="container">
        <div class="status-card">
            <div class="status-icon">
                <i class="fas fa-ban"></i>
            </div>
            
            <h1 class="status-title">Compte Suspendu</h1>
            
            <p class="status-message">
                Votre compte créateur a été suspendu. Vous ne pouvez plus accéder à votre espace vendeur pour le moment.
            </p>
            
            <div class="status-info">
                <h4>Que faire maintenant ?</h4>
                <p>
                    Si vous pensez qu'il s'agit d'une erreur ou si vous souhaitez obtenir plus d'informations, 
                    veuillez contacter notre équipe support via l'adresse email : 
                    <strong style="color: #D4A574;">support@racinebyganda.com</strong>
                </p>
            </div>
            
            <form method="POST" action="{{ route('creator.logout') }}">
                @csrf
                <button type="submit" class="btn-action">
                    <i class="fas fa-sign-out-alt"></i>
                    Se Déconnecter
                </button>
            </form>
            
            <a href="{{ route('frontend.home') }}" class="btn-secondary">
                <i class="fas fa-home"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>

