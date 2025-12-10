@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Amira Ganda - Fondatrice & CEO - RACINE BY GANDA')

@push('styles')
<style>
    .ceo-hero {
        min-height: 100vh;
        background: linear-gradient(135deg, #1a0f09 0%, #2C1810 50%, #1a0f09 100%);
        display: flex;
        align-items: center;
        margin-top: -70px;
        padding-top: 70px;
        position: relative;
        overflow: hidden;
    }
    
    .ceo-hero::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 60%;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23D4A574' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.5;
    }
    
    .ceo-hero-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
        position: relative;
        z-index: 2;
    }
    
    .ceo-photo-container {
        position: relative;
    }
    
    .ceo-photo {
        width: 100%;
        max-width: 450px;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        position: relative;
    }
    
    .ceo-photo::before {
        content: '';
        position: absolute;
        inset: 0;
        border: 3px solid rgba(212, 165, 116, 0.3);
        border-radius: 24px;
        z-index: 1;
    }
    
    .ceo-photo img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .ceo-decoration {
        position: absolute;
        width: 200px;
        height: 200px;
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 50%;
        top: -30px;
        right: -30px;
    }
    
    .ceo-decoration-2 {
        position: absolute;
        width: 150px;
        height: 150px;
        background: linear-gradient(135deg, rgba(237, 95, 30, 0.1) 0%, transparent 100%);
        border-radius: 50%;
        bottom: -20px;
        left: -20px;
    }
    
    .ceo-info h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 4rem;
        color: white;
        margin-bottom: 0.5rem;
        line-height: 1.1;
    }
    
    .ceo-info .title {
        color: #D4A574;
        font-size: 1.25rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
        letter-spacing: 2px;
    }
    
    .ceo-info .quote {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-style: italic;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 2rem;
        padding-left: 1.5rem;
        border-left: 3px solid #D4A574;
    }
    
    .ceo-social {
        display: flex;
        gap: 1rem;
    }
    
    .ceo-social a {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        font-size: 1.1rem;
        transition: all 0.3s;
    }
    
    .ceo-social a:hover {
        background: #ED5F1E;
        border-color: #ED5F1E;
        transform: translateY(-3px);
    }
    
    /* BIO SECTION */
    .ceo-bio {
        padding: 5rem 0;
        background: white;
    }
    
    .bio-content {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .bio-intro {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.75rem;
        color: #2C1810;
        text-align: center;
        margin-bottom: 3rem;
        line-height: 1.6;
    }
    
    .bio-intro span {
        color: #ED5F1E;
    }
    
    .bio-text {
        columns: 2;
        column-gap: 3rem;
        color: #5C4A3D;
        line-height: 1.8;
        font-size: 1.05rem;
    }
    
    .bio-text p {
        margin-bottom: 1.5rem;
    }
    
    /* TIMELINE */
    .ceo-timeline {
        padding: 5rem 0;
        background: #F8F6F3;
    }
    
    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-title h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .section-title .line {
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #D4A574, #ED5F1E);
        margin: 0 auto;
    }
    
    .timeline {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 100%;
        background: linear-gradient(to bottom, #D4A574, #ED5F1E);
    }
    
    .timeline-item {
        display: flex;
        margin-bottom: 3rem;
    }
    
    .timeline-item:nth-child(odd) {
        flex-direction: row-reverse;
    }
    
    .timeline-content {
        width: 45%;
        background: white;
        padding: 1.5rem 2rem;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        position: relative;
    }
    
    .timeline-content::before {
        content: '';
        position: absolute;
        width: 15px;
        height: 15px;
        background: #ED5F1E;
        border-radius: 50%;
        top: 1.5rem;
    }
    
    .timeline-item:nth-child(odd) .timeline-content::before {
        left: -32px;
    }
    
    .timeline-item:nth-child(even) .timeline-content::before {
        right: -32px;
    }
    
    .timeline-year {
        display: inline-block;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    
    .timeline-content h4 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.25rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .timeline-content p {
        color: #8B7355;
        font-size: 0.9rem;
        line-height: 1.6;
    }
    
    /* VALUES */
    .ceo-values {
        padding: 5rem 0;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
    }
    
    .values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .value-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s;
    }
    
    .value-card:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-5px);
    }
    
    .value-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 1.75rem;
        color: white;
    }
    
    .value-card h4 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.35rem;
        color: white;
        margin-bottom: 0.75rem;
    }
    
    .value-card p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* CTA */
    .ceo-cta {
        padding: 5rem 0;
        background: white;
        text-align: center;
    }
    
    .ceo-cta h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        color: #2C1810;
        margin-bottom: 1rem;
    }
    
    .ceo-cta p {
        color: #8B7355;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(237, 95, 30, 0.3);
        color: white;
    }
    
    @media (max-width: 992px) {
        .ceo-hero-content {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .ceo-photo-container {
            order: -1;
        }
        
        .ceo-photo {
            margin: 0 auto;
            max-width: 350px;
        }
        
        .ceo-info h1 {
            font-size: 3rem;
        }
        
        .ceo-info .quote {
            border-left: none;
            padding-left: 0;
            border-top: 3px solid #D4A574;
            padding-top: 1rem;
        }
        
        .ceo-social {
            justify-content: center;
        }
        
        .bio-text {
            columns: 1;
        }
        
        .timeline::before {
            left: 20px;
        }
        
        .timeline-item,
        .timeline-item:nth-child(odd) {
            flex-direction: row;
        }
        
        .timeline-content {
            width: calc(100% - 50px);
            margin-left: 50px;
        }
        
        .timeline-item:nth-child(odd) .timeline-content::before,
        .timeline-item:nth-child(even) .timeline-content::before {
            left: -32px;
            right: auto;
        }
        
        .values-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="ceo-hero">
    <div class="container">
        <div class="ceo-hero-content">
            <div class="ceo-photo-container">
                <div class="ceo-decoration"></div>
                <div class="ceo-decoration-2"></div>
                <div class="ceo-photo">
                    <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=600&h=800&fit=crop&crop=faces" alt="Amira Ganda - CEO RACINE BY GANDA">
                </div>
            </div>
            
            <div class="ceo-info">
                @php
                    $heroSection = $cmsPage?->section('hero');
                    $heroData = $heroSection?->data ?? [];
                @endphp
                <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Amira Ganda' }}</h1>
                <p class="title">{{ $heroData['subtitle'] ?? 'FONDATRICE & CEO' }}</p>
                <p class="quote">
                    {{ $heroData['quote'] ?? "La mode africaine n'est pas une tendance, c'est un héritage vivant que nous avons le devoir de préserver et de sublimer." }}
                </p>
                <div class="ceo-social">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BIO -->
<section class="ceo-bio">
    <div class="container">
        <div class="bio-content">
            <p class="bio-intro">
                Visionnaire, créatrice et ambassadrice de la mode africaine, <span>Amira Ganda</span> a fondé RACINE BY GANDA avec une mission claire : célébrer l'héritage africain à travers une mode raffinée et contemporaine.
            </p>
            
            <div class="bio-text">
                <p>
                    Née à Pointe-Noire, République du Congo, Amira a grandi entourée des tissus colorés et des motifs traditionnels qui ont façonné son regard sur la mode. Dès son plus jeune âge, elle a développé une passion pour la création et le design, passant des heures à observer les couturières du quartier et à dessiner ses propres modèles.
                </p>
                <p>
                    Après des études en design de mode à Paris et une expérience dans plusieurs maisons de couture européennes, Amira a fait le choix audacieux de revenir au Congo pour créer sa propre marque. RACINE BY GANDA est né de cette volonté de réconcilier héritage et modernité, tradition et innovation.
                </p>
                <p>
                    Sous sa direction, la marque est devenue un symbole de l'excellence africaine, reconnue pour la qualité de ses créations et son engagement envers l'artisanat local. Amira collabore avec des artisans congolais et africains, contribuant au développement économique de la région tout en préservant des savoir-faire ancestraux.
                </p>
                <p>
                    Aujourd'hui, Amira est reconnue comme l'une des voix les plus influentes de la mode africaine. Elle intervient régulièrement lors de conférences internationales et milite pour une industrie de la mode plus éthique et inclusive. Sa vision : faire de l'Afrique une destination incontournable de la mode mondiale.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- TIMELINE -->
<section class="ceo-timeline">
    <div class="container">
        <div class="section-title">
            <h2>Parcours</h2>
            <div class="line"></div>
        </div>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2010</span>
                    <h4>Études à Paris</h4>
                    <p>Diplôme en Design de Mode à l'École de la Chambre Syndicale de la Couture Parisienne.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2014</span>
                    <h4>Expérience internationale</h4>
                    <p>Collaboration avec plusieurs maisons de couture à Paris et Milan.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2017</span>
                    <h4>Retour au Congo</h4>
                    <p>Retour aux sources et début du projet RACINE BY GANDA.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2018</span>
                    <h4>Lancement officiel</h4>
                    <p>Première collection et ouverture de l'atelier à Pointe-Noire.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2020</span>
                    <h4>Reconnaissance internationale</h4>
                    <p>Participation à la Fashion Week de Lagos et prix de la meilleure marque émergente.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2024</span>
                    <h4>Expansion</h4>
                    <p>Ouverture du showroom et lancement de la plateforme e-commerce internationale.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VALUES -->
<section class="ceo-values">
    <div class="container">
        <div class="section-title" style="color: white;">
            <h2 style="color: white;">Mes Valeurs</h2>
            <div class="line"></div>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🌍</div>
                <h4>Authenticité</h4>
                <p>Rester fidèle à nos racines africaines tout en embrassant la modernité et l'innovation.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">⭐</div>
                <h4>Excellence</h4>
                <p>Chaque création doit refléter le plus haut niveau de qualité et d'attention aux détails.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">🤝</div>
                <h4>Communauté</h4>
                <p>Soutenir les artisans locaux et contribuer au développement de notre région.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="ceo-cta">
    <div class="container">
        <h2>Rencontrons-nous</h2>
        <p>Pour les demandes de collaboration, interviews ou événements</p>
        <a href="{{ route('frontend.contact') }}" class="btn-cta">
            <i class="fas fa-envelope"></i> Contactez-nous
        </a>
    </div>
</section>
@endsection

