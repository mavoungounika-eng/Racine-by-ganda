<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisissez votre espace - RACINE BY GANDA</title>
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
            overflow: hidden;
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
            max-width: 1100px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        
        .header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .logo-mark {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
        }
        
        .logo-text {
            font-family: 'Libre Baskerville', serif;
            font-size: 2rem;
            color: white;
            letter-spacing: 4px;
        }
        
        .header h1 {
            font-family: 'Libre Baskerville', serif;
            font-size: 2.5rem;
            font-weight: 400;
            color: white;
            margin-bottom: 1rem;
        }
        
        .header p {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.5);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .portal-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        
        .portal-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s;
        }
        
        .portal-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        }
        
        .portal-card:hover::before {
            transform: scaleX(1);
        }
        
        .portal-card.client {
            --accent: #D4A574;
            --accent-light: #E5B27B;
        }
        
        .portal-card.team {
            --accent: #FF6B00;
            --accent-light: #FFB800;
        }
        
        .card-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .card-icon i {
            font-size: 1.5rem;
            color: #111;
        }
        
        .portal-card h3 {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .portal-card p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .card-features {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        
        .card-features span {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .card-features i {
            color: var(--accent);
            font-size: 0.8rem;
        }
        
        .card-cta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--accent);
            font-weight: 600;
            font-size: 1rem;
            transition: gap 0.3s;
        }
        
        .portal-card:hover .card-cta {
            gap: 1rem;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: rgba(255, 255, 255, 0.8);
        }
        
        @media (max-width: 768px) {
            .cards-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 1.75rem; }
        }
    </style>
</head>
<body>
    <div class="gradient-mesh"></div>
    <div class="noise"></div>
    
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-mark">R</div>
                <span class="logo-text">RACINE</span>
            </div>
            <h1>Bienvenue</h1>
            <p>Choisissez votre espace pour accéder à la plateforme RACINE BY GANDA</p>
        </div>
        
        <div class="cards-grid">
            <a href="{{ route('login', ['context' => 'boutique']) }}" class="portal-card client">
                <div class="card-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>Espace Boutique</h3>
                <p>Clients et créateurs, accédez à votre espace personnel et découvrez nos collections.</p>
                <div class="card-features">
                    <span><i class="fas fa-check"></i> Commandes & suivi</span>
                    <span><i class="fas fa-check"></i> Wishlist & favoris</span>
                    <span><i class="fas fa-check"></i> Offres exclusives</span>
                </div>
                <span class="card-cta">
                    Se connecter <i class="fas fa-arrow-right"></i>
                </span>
            </a>
            
            <a href="{{ route('login', ['context' => 'equipe']) }}" class="portal-card team">
                <div class="card-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>Espace Équipe</h3>
                <p>Accès réservé aux membres de l'équipe RACINE pour la gestion quotidienne.</p>
                <div class="card-features">
                    <span><i class="fas fa-check"></i> Dashboard ERP</span>
                    <span><i class="fas fa-check"></i> Gestion des commandes</span>
                    <span><i class="fas fa-check"></i> Analytics avancés</span>
                </div>
                <span class="card-cta">
                    Accéder au portail <i class="fas fa-arrow-right"></i>
                </span>
            </a>
        </div>
        
        <div class="footer-links">
            <a href="{{ route('frontend.home') }}">
                <i class="fas fa-arrow-left"></i> Retour à la boutique
            </a>
            <a href="{{ route('register', ['context' => 'boutique']) }}">
                <i class="fas fa-user-plus"></i> Créer un compte
            </a>
        </div>
    </div>
</body>
</html>
