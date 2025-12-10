@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Notre Histoire - RACINE BY GANDA')

@push('styles')
<style>
    /* HERO */
    .about-hero {
        min-height: 70vh;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        margin-top: -70px;
        padding-top: 70px;
    }
    
    .about-hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4A574' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }
    
    .hero-text {
        color: white;
    }
    
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(212, 165, 116, 0.15);
        border: 1px solid rgba(212, 165, 116, 0.3);
        padding: 0.5rem 1.25rem;
        border-radius: 30px;
        font-size: 0.8rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #D4A574;
        margin-bottom: 1.5rem;
    }
    
    .hero-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 4rem;
        font-weight: 600;
        line-height: 1.1;
        margin-bottom: 1.5rem;
    }
    
    .hero-title .highlight {
        color: #D4A574;
    }
    
    .hero-description {
        font-size: 1.15rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.7);
        max-width: 500px;
    }
    
    .hero-image {
        position: relative;
    }
    
    .hero-img-main {
        width: 100%;
        height: 500px;
        object-fit: cover;
        border-radius: 24px;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
    }
    
    .hero-img-overlay {
        position: absolute;
        bottom: -30px;
        left: -30px;
        width: 200px;
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    }
    
    .hero-img-overlay h4 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 700;
        color: #D4A574;
        margin-bottom: 0.25rem;
    }
    
    .hero-img-overlay span {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    /* STORY SECTION */
    .story-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .story-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }
    
    .story-images {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .story-img {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }
    
    .story-img:first-child {
        grid-row: span 2;
    }
    
    .story-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .section-tag {
        display: inline-block;
        background: rgba(212, 165, 116, 0.1);
        color: #8B5A2B;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    
    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.75rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }
    
    .story-text p {
        color: #5C4A3D;
        font-size: 1.1rem;
        line-height: 1.9;
        margin-bottom: 1.5rem;
    }
    
    /* VALUES */
    .values-section {
        padding: 6rem 0;
        background: white;
    }
    
    .values-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .values-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }
    
    .value-card {
        text-align: center;
        padding: 2rem;
        background: #F8F6F3;
        border-radius: 20px;
        transition: all 0.3s;
    }
    
    .value-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
    }
    
    .value-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2rem;
        color: white;
    }
    
    .value-card h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .value-card p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* TEAM */
    .team-section {
        padding: 6rem 0;
        background: #2C1810;
    }
    
    .team-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .team-header .section-tag {
        background: rgba(212, 165, 116, 0.2);
    }
    
    .team-header .section-title {
        color: white;
    }
    
    .team-header p {
        color: rgba(255, 255, 255, 0.6);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .team-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .team-card:hover {
        transform: translateY(-10px);
        background: rgba(255, 255, 255, 0.1);
    }
    
    .team-img {
        height: 300px;
        overflow: hidden;
    }
    
    .team-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .team-card:hover .team-img img {
        transform: scale(1.1);
    }
    
    .team-info {
        padding: 1.5rem;
        text-align: center;
    }
    
    .team-info h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.25rem;
    }
    
    .team-info span {
        color: #D4A574;
        font-size: 0.9rem;
    }
    
    .team-info p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.9rem;
        margin-top: 1rem;
        line-height: 1.6;
    }
    
    /* TIMELINE */
    .timeline-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .timeline-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .timeline {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #D4A574;
        transform: translateX(-50%);
    }
    
    .timeline-item {
        display: flex;
        margin-bottom: 3rem;
    }
    
    .timeline-item:nth-child(odd) {
        flex-direction: row-reverse;
    }
    
    .timeline-content {
        width: calc(50% - 40px);
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    
    .timeline-year {
        display: inline-block;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        padding: 0.35rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    
    .timeline-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .timeline-content p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .timeline-dot {
        width: 20px;
        height: 20px;
        background: #D4A574;
        border: 4px solid #F8F6F3;
        border-radius: 50%;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* CTA */
    .cta-section {
        padding: 5rem 0;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        text-align: center;
    }
    
    .cta-section h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 600;
        color: white;
        margin-bottom: 1rem;
    }
    
    .cta-section p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1rem;
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2.5rem;
        background: white;
        color: #2C1810;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        color: #2C1810;
    }
    
    @media (max-width: 1024px) {
        .hero-content, .story-grid, .team-grid { grid-template-columns: 1fr; }
        .hero-image { display: none; }
        .hero-title { font-size: 3rem; }
        .values-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .values-grid { grid-template-columns: 1fr; }
        .timeline::before { left: 20px; }
        .timeline-item, .timeline-item:nth-child(odd) { flex-direction: row; }
        .timeline-content { width: calc(100% - 60px); margin-left: 40px; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="about-hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                @php
                    $heroSection = $cmsPage?->section('hero');
                    $heroData = $heroSection?->data ?? [];
                @endphp
                <span class="hero-badge">{{ $heroData['badge'] ?? 'Notre Histoire' }}</span>
                <h1 class="hero-title">
                    {!! $heroData['title'] ?? "Célébrer la<br><span class=\"highlight\">Beauté</span><br>Africaine" !!}
                </h1>
                <p class="hero-description">
                    {{ $heroData['description'] ?? "RACINE BY GANDA est née d'une passion profonde pour l'artisanat africain et du désir de créer un pont entre les talents du continent et le monde entier." }}
                </p>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=800&h=1000&fit=crop" alt="Artisanat Africain" class="hero-img-main">
                <div class="hero-img-overlay">
                    <h4>2019</h4>
                    <span>Année de création</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STORY -->
<section class="story-section">
    <div class="container">
        <div class="story-grid">
            <div class="story-images">
                <div class="story-img">
                    <img src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600" alt="Création">
                </div>
                <div class="story-img">
                    <img src="https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400" alt="Tissu">
                </div>
                <div class="story-img">
                    <img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?w=400" alt="Atelier">
                </div>
            </div>
            <div class="story-content">
                <span class="section-tag">Notre Vision</span>
                <h2 class="section-title">Une Mode Éthique et Authentique</h2>
                <div class="story-text">
                    <p>
                        Tout a commencé par un voyage au Sénégal, où notre fondatrice a été émerveillée par 
                        la richesse des textiles et le savoir-faire exceptionnel des artisans locaux. 
                        De cette rencontre est née l'envie de partager ces trésors avec le monde.
                    </p>
                    <p>
                        Aujourd'hui, RACINE BY GANDA collabore avec plus de 50 artisans et créateurs 
                        répartis dans 15 pays africains. Chaque pièce que vous achetez contribue directement 
                        à améliorer les conditions de vie de ces communautés talentueuses.
                    </p>
                    <p>
                        Notre engagement ? Proposer une mode responsable qui respecte les traditions tout 
                        en les faisant évoluer vers une esthétique contemporaine.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VALUES -->
<section class="values-section">
    <div class="container">
        <div class="values-header">
            <span class="section-tag">Nos Valeurs</span>
            <h2 class="section-title">Ce qui nous anime</h2>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-leaf"></i></div>
                <h3>Durabilité</h3>
                <p>Des matériaux naturels et des procédés de fabrication respectueux de l'environnement.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-hands-helping"></i></div>
                <h3>Commerce Équitable</h3>
                <p>Une rémunération juste pour chaque artisan impliqué dans la création de nos pièces.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-gem"></i></div>
                <h3>Authenticité</h3>
                <p>Des créations uniques qui célèbrent le patrimoine et les traditions africaines.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-heart"></i></div>
                <h3>Passion</h3>
                <p>L'amour de l'artisanat et le désir de partager la beauté de l'Afrique avec le monde.</p>
            </div>
        </div>
    </div>
</section>

<!-- TEAM -->
<section class="team-section">
    <div class="container">
        <div class="team-header">
            <span class="section-tag">L'Équipe</span>
            <h2 class="section-title">Les visages derrière RACINE BY GANDA</h2>
            <p>Une équipe passionnée, unie par l'amour de l'artisanat africain</p>
        </div>
        
        <div class="team-grid">
            <div class="team-card">
                <div class="team-img">
                    <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&h=400&fit=crop&crop=faces" alt="Fondatrice">
                </div>
                <div class="team-info">
                    <h3>Aminata Ganda</h3>
                    <span>Fondatrice & Directrice Créative</span>
                    <p>Visionnaire passionnée, elle a créé RACINE BY GANDA pour partager sa culture avec le monde.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-img">
                    <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=faces" alt="Directeur">
                </div>
                <div class="team-info">
                    <h3>Moussa Diallo</h3>
                    <span>Directeur des Opérations</span>
                    <p>Expert en logistique, il coordonne les relations avec nos artisans partenaires.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-img">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=400&fit=crop&crop=faces" alt="Designer">
                </div>
                <div class="team-info">
                    <h3>Fatou Ndiaye</h3>
                    <span>Cheffe Styliste</span>
                    <p>Elle sublime les créations traditionnelles avec une touche de modernité.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TIMELINE -->
<section class="timeline-section">
    <div class="container">
        <div class="timeline-header">
            <span class="section-tag">Notre Parcours</span>
            <h2 class="section-title">Les moments clés</h2>
        </div>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2019</span>
                    <h3>La Naissance</h3>
                    <p>Création de RACINE BY GANDA à Paris, avec une première collection de 20 pièces.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2020</span>
                    <h3>L'Expansion</h3>
                    <p>Partenariat avec 15 artisans au Sénégal et au Mali. Lancement de la boutique en ligne.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2022</span>
                    <h3>La Reconnaissance</h3>
                    <p>Prix de la Mode Éthique au salon Ethical Fashion Show. 50 créateurs partenaires.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <span class="timeline-year">2024</span>
                    <h3>Aujourd'hui</h3>
                    <p>Plus de 5000 clients satisfaits et présence dans 15 pays africains.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Rejoignez l'Aventure RACINE BY GANDA</h2>
        <p>Découvrez nos créations uniques et participez à une mode plus responsable et authentique.</p>
        <a href="{{ route('frontend.shop') }}" class="btn-cta">
            <i class="fas fa-shopping-bag"></i>
            Explorer la boutique
        </a>
    </div>
</section>
@endsection
