@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Nos Créateurs - RACINE BY GANDA')

@push('styles')
<style>
    .creators-hero {
        min-height: 60vh;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        display: flex;
        align-items: center;
        position: relative;
        margin-top: -70px;
        padding-top: 70px;
        overflow: hidden;
    }
    
    .creators-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 80%;
        height: 200%;
        background: radial-gradient(ellipse, rgba(212, 165, 116, 0.1) 0%, transparent 60%);
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 4rem 0;
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
        color: white;
        line-height: 1.1;
        margin-bottom: 1.5rem;
    }
    
    .hero-title .highlight {
        color: #D4A574;
    }
    
    .hero-description {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.7);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.8;
    }
    
    /* STATS */
    .stats-bar {
        background: #D4A574;
        padding: 2rem 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
        text-align: center;
    }
    
    .stat-item h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.25rem;
    }
    
    .stat-item span {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.95rem;
    }
    
    /* FEATURED CREATOR */
    .featured-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .featured-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }
    
    .featured-image {
        position: relative;
    }
    
    .featured-img {
        width: 100%;
        height: 600px;
        object-fit: cover;
        border-radius: 24px;
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.15);
    }
    
    .featured-badge {
        position: absolute;
        top: 2rem;
        left: 2rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
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
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    
    .featured-content p {
        color: #5C4A3D;
        font-size: 1.1rem;
        line-height: 1.9;
        margin-bottom: 1.5rem;
    }
    
    .featured-meta {
        display: flex;
        gap: 2rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .meta-item i {
        color: #D4A574;
        font-size: 1.25rem;
    }
    
    .meta-item span {
        color: #5C4A3D;
    }
    
    .btn-view-collection {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 2rem;
        transition: all 0.3s;
    }
    
    .btn-view-collection:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(44, 24, 16, 0.3);
        color: white;
    }
    
    /* CREATORS GRID */
    .creators-section {
        padding: 6rem 0;
        background: white;
    }
    
    .creators-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .creators-filter {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 3rem;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 0.6rem 1.5rem;
        border: 2px solid #E5DDD3;
        background: white;
        border-radius: 30px;
        color: #5C4A3D;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .filter-btn:hover, .filter-btn.active {
        background: #2C1810;
        border-color: #2C1810;
        color: white;
    }
    
    .creators-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .creator-card {
        background: #F8F6F3;
        border-radius: 24px;
        overflow: hidden;
        transition: all 0.4s;
    }
    
    .creator-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.12);
    }
    
    .creator-image {
        height: 280px;
        position: relative;
        overflow: hidden;
    }
    
    .creator-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .creator-card:hover .creator-image img {
        transform: scale(1.1);
    }
    
    .creator-country {
        position: absolute;
        bottom: 1rem;
        left: 1rem;
        background: rgba(255, 255, 255, 0.95);
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #2C1810;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .creator-info {
        padding: 1.5rem;
        text-align: center;
    }
    
    .creator-info h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .creator-specialty {
        color: #D4A574;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    
    .creator-bio {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    
    .creator-stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .creator-stats div {
        text-align: center;
    }
    
    .creator-stats strong {
        display: block;
        font-size: 1.25rem;
        color: #2C1810;
    }
    
    .creator-stats span {
        font-size: 0.8rem;
        color: #8B7355;
    }
    
    /* CTA */
    .cta-section {
        padding: 6rem 0;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        text-align: center;
    }
    
    .cta-section h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.75rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .cta-section p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.15rem;
        max-width: 600px;
        margin: 0 auto 2rem;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2.5rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(212, 165, 116, 0.4);
        color: white;
    }
    
    @media (max-width: 1024px) {
        .hero-title { font-size: 3rem; }
        .featured-grid { grid-template-columns: 1fr; }
        .creators-grid { grid-template-columns: repeat(2, 1fr); }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; }
        .creators-grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="creators-hero">
    <div class="container">
        <div class="hero-content">
            @php
                $heroSection = $cmsPage?->section('hero');
                $heroData = $heroSection?->data ?? [];
            @endphp
            <span class="hero-badge">{{ $heroData['badge'] ?? '✨ Artisans d\'Excellence' }}</span>
            <h1 class="hero-title">
                {!! $heroData['title'] ?? 'Les <span class="highlight">Talents</span> qui<br>font RACINE' !!}
            </h1>
            <p class="hero-description">
                {{ $heroData['description'] ?? 'Découvrez les artisans et créateurs passionnés derrière chaque pièce unique. Leur savoir-faire ancestral rencontre une vision contemporaine.' }}
            </p>
            
            {{-- CTA Marketplace --}}
            <div class="mt-4">
                <a href="{{ route('frontend.marketplace') }}" class="btn btn-lg" 
                   style="background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%); color: white; padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.75rem; font-weight: 600;">
                    <i class="fas fa-shopping-bag"></i>
                    Voir tous les produits au Marketplace
                    <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.85rem;">{{ $totalProducts }} produits</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>{{ $creators->total() }}</h3>
                <span>Créateurs partenaires</span>
            </div>
            <div class="stat-item">
                <h3>{{ $totalProducts }}</h3>
                <span>Produits au Marketplace</span>
            </div>
            <div class="stat-item">
                <h3>100%</h3>
                <span>Fait main</span>
            </div>
            <div class="stat-item">
                <h3>15+</h3>
                <span>Pays représentés</span>
            </div>
        </div>
    </div>
</section>

<!-- FEATURED -->
<section class="featured-section">
    <div class="container">
        <div class="featured-grid">
            <div class="featured-image">
                <img src="https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=800" alt="Créatrice vedette" class="featured-img">
                <span class="featured-badge">⭐ Créatrice du mois</span>
            </div>
            <div class="featured-content">
                <span class="section-tag">À la Une</span>
                <h2 class="section-title">Amina Diallo</h2>
                <p>
                    Originaire de Dakar, Amina perpétue un savoir-faire familial vieux de trois générations. 
                    Ses créations en wax mêlent tradition et modernité avec une maîtrise exceptionnelle.
                </p>
                <p>
                    "Chaque pièce que je crée raconte une histoire. Mon objectif est de sublimer notre 
                    patrimoine textile tout en le rendant accessible au monde entier."
                </p>
                <div class="featured-meta">
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Dakar, Sénégal</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Depuis 2019</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-box"></i>
                        <span>85 créations</span>
                    </div>
                </div>
                <a href="{{ route('frontend.shop') }}" class="btn-view-collection">
                    <i class="fas fa-eye"></i> Voir sa collection
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CREATORS -->
<section class="creators-section">
    <div class="container">
        <div class="creators-header">
            <span class="section-tag">Nos Artisans</span>
            <h2 class="section-title">Tous nos créateurs</h2>
        </div>
        
        <div class="creators-filter">
            <button class="filter-btn active">Tous</button>
            <button class="filter-btn">Sénégal</button>
            <button class="filter-btn">Ghana</button>
            <button class="filter-btn">Côte d'Ivoire</button>
            <button class="filter-btn">Mali</button>
            <button class="filter-btn">Cameroun</button>
        </div>
        
        <div class="creators-grid">
            <div class="creator-card">
                <div class="creator-image">
                    <img src="https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=500" alt="Amina Diallo">
                    <span class="creator-country">🇸🇳 Sénégal</span>
                </div>
                <div class="creator-info">
                    <h3>Amina Diallo</h3>
                    <p class="creator-specialty">Styliste Wax</p>
                    <p class="creator-bio">Créatrice passionnée, elle sublime le wax avec une touche contemporaine unique.</p>
                    <div class="creator-stats">
                        <div><strong>85</strong><span>Créations</span></div>
                        <div><strong>4.9</strong><span>Note</span></div>
                    </div>
                </div>
            </div>
            
            <div class="creator-card">
                <div class="creator-image">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500" alt="Kwame Asante">
                    <span class="creator-country">🇬🇭 Ghana</span>
                </div>
                <div class="creator-info">
                    <h3>Kwame Asante</h3>
                    <p class="creator-specialty">Maître Kente</p>
                    <p class="creator-bio">Expert en tissage kente, il perpétue une tradition familiale de 3 générations.</p>
                    <div class="creator-stats">
                        <div><strong>62</strong><span>Créations</span></div>
                        <div><strong>4.8</strong><span>Note</span></div>
                    </div>
                </div>
            </div>
            
            <div class="creator-card">
                <div class="creator-image">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500" alt="Fatou Ndiaye">
                    <span class="creator-country">🇨🇮 Côte d'Ivoire</span>
                </div>
                <div class="creator-info">
                    <h3>Fatou Ndiaye</h3>
                    <p class="creator-specialty">Accessoiriste</p>
                    <p class="creator-bio">Créatrice de bijoux et accessoires inspirés des motifs traditionnels.</p>
                    <div class="creator-stats">
                        <div><strong>124</strong><span>Créations</span></div>
                        <div><strong>4.9</strong><span>Note</span></div>
                    </div>
                </div>
            </div>
            
            <div class="creator-card">
                <div class="creator-image">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=500" alt="Moussa Keita">
                    <span class="creator-country">🇲🇱 Mali</span>
                </div>
                <div class="creator-info">
                    <h3>Moussa Keita</h3>
                    <p class="creator-specialty">Teinturier Bogolan</p>
                    <p class="creator-bio">Artisan spécialisé dans le bogolan, technique ancestrale malienne.</p>
                    <div class="creator-stats">
                        <div><strong>45</strong><span>Créations</span></div>
                        <div><strong>4.7</strong><span>Note</span></div>
                    </div>
                </div>
            </div>
            
            <div class="creator-card">
                <div class="creator-image">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500" alt="Aïcha Camara">
                    <span class="creator-country">🇨🇲 Cameroun</span>
                </div>
                <div class="creator-info">
                    <h3>Aïcha Camara</h3>
                    <p class="creator-specialty">Créatrice Haute Couture</p>
                    <p class="creator-bio">Elle fusionne les tissus africains avec des coupes haute couture.</p>
                    <div class="creator-stats">
                        <div><strong>38</strong><span>Créations</span></div>
                        <div><strong>5.0</strong><span>Note</span></div>
                    </div>
                </div>
            </div>
            
            <div class="creator-card">
                <div class="creator-image">
                    <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=500" alt="Ibrahim Sow">
                    <span class="creator-country">🇸🇳 Sénégal</span>
                </div>
                <div class="creator-info">
                    <h3>Ibrahim Sow</h3>
                    <p class="creator-specialty">Couturier Homme</p>
                    <p class="creator-bio">Spécialiste de la mode masculine africaine contemporaine.</p>
                    <div class="creator-stats">
                        <div><strong>56</strong><span>Créations</span></div>
                        <div><strong>4.8</strong><span>Note</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Vous êtes créateur ?</h2>
        <p>Rejoignez notre communauté d'artisans et partagez votre talent avec le monde entier.</p>
        <a href="{{ route('frontend.contact') }}" class="btn-cta">
            <i class="fas fa-handshake"></i> Devenir partenaire
        </a>
    </div>
</section>
@endsection
