@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'L\'Atelier - RACINE BY GANDA')

@push('styles')
<style>
    .atelier-hero {
        min-height: 80vh;
        background: linear-gradient(135deg, rgba(44, 24, 16, 0.95) 0%, rgba(26, 15, 9, 0.98) 100%),
                    url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1600') center/cover;
        display: flex;
        align-items: center;
        position: relative;
        margin-top: -70px;
        padding-top: 70px;
    }
    
    .hero-content {
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
        background: rgba(212, 165, 116, 0.2);
        border: 1px solid rgba(212, 165, 116, 0.4);
        padding: 0.6rem 1.5rem;
        border-radius: 30px;
        font-size: 0.85rem;
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
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 2rem;
    }
    
    .hero-cta {
        display: flex;
        gap: 1rem;
    }
    
    .btn-primary-hero {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-primary-hero:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(212, 165, 116, 0.3);
        color: white;
    }
    
    .btn-outline-hero {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-outline-hero:hover {
        background: white;
        color: #2C1810;
        border-color: white;
    }
    
    .hero-video {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
    }
    
    .hero-video img {
        width: 100%;
        height: 450px;
        object-fit: cover;
    }
    
    .play-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
    }
    
    .play-btn:hover {
        transform: translate(-50%, -50%) scale(1.1);
        box-shadow: 0 0 40px rgba(212, 165, 116, 0.5);
    }
    
    /* PROCESS */
    .process-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 4rem;
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
    }
    
    .section-subtitle {
        color: #8B7355;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .process-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
        position: relative;
    }
    
    .process-grid::before {
        content: '';
        position: absolute;
        top: 60px;
        left: 15%;
        right: 15%;
        height: 2px;
        background: linear-gradient(90deg, #D4A574, #8B5A2B);
        z-index: 0;
    }
    
    .process-step {
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-number {
        width: 80px;
        height: 80px;
        background: white;
        border: 3px solid #D4A574;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-weight: 700;
        color: #D4A574;
        margin: 0 auto 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .process-step h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .process-step p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* GALLERY */
    .gallery-section {
        padding: 6rem 0;
        background: white;
    }
    
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    .gallery-item {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        cursor: pointer;
    }
    
    .gallery-item.large {
        grid-row: span 2;
    }
    
    .gallery-item img {
        width: 100%;
        height: 100%;
        min-height: 250px;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .gallery-item.large img {
        min-height: 100%;
    }
    
    .gallery-item:hover img {
        transform: scale(1.1);
    }
    
    .gallery-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(44, 24, 16, 0.8) 0%, transparent 50%);
        display: flex;
        align-items: flex-end;
        padding: 1.5rem;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .gallery-item:hover .gallery-overlay {
        opacity: 1;
    }
    
    .gallery-overlay span {
        color: white;
        font-weight: 600;
    }
    
    /* SERVICES */
    .services-section {
        padding: 6rem 0;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
    }
    
    .services-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .services-header .section-tag {
        background: rgba(212, 165, 116, 0.2);
    }
    
    .services-header .section-title {
        color: white;
    }
    
    .services-header .section-subtitle {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .service-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 2.5rem;
        text-align: center;
        transition: all 0.3s;
    }
    
    .service-card:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-10px);
    }
    
    .service-icon {
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
    
    .service-card h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.75rem;
    }
    
    .service-card p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    
    .service-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #D4A574;
    }
    
    .service-price span {
        font-size: 0.9rem;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.5);
    }
    
    /* TESTIMONIAL */
    .testimonial-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .testimonial-content {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }
    
    .quote-icon {
        font-size: 4rem;
        color: #D4A574;
        margin-bottom: 2rem;
    }
    
    .testimonial-text {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-style: italic;
        color: #2C1810;
        line-height: 1.6;
        margin-bottom: 2rem;
    }
    
    .testimonial-author {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }
    
    .testimonial-author img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .author-info h4 {
        font-weight: 600;
        color: #2C1810;
    }
    
    .author-info span {
        color: #8B7355;
        font-size: 0.9rem;
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
        color: white;
        margin-bottom: 1rem;
    }
    
    .cta-section p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2.5rem;
        background: white;
        color: #2C1810;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        color: #2C1810;
    }
    
    @media (max-width: 1024px) {
        .hero-content { grid-template-columns: 1fr; }
        .hero-video { display: none; }
        .hero-title { font-size: 3rem; }
        .process-grid { grid-template-columns: repeat(2, 1fr); }
        .process-grid::before { display: none; }
        .gallery-grid { grid-template-columns: repeat(2, 1fr); }
        .gallery-item.large { grid-row: span 1; }
        .services-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; }
        .process-grid { grid-template-columns: 1fr; }
        .gallery-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="atelier-hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                @php
                    $heroSection = $cmsPage?->section('hero');
                    $heroData = $heroSection?->data ?? [];
                @endphp
                <span class="hero-badge">{{ $heroData['badge'] ?? '✂️ Artisanat d\'Excellence' }}</span>
                <h1 class="hero-title">
                    {!! $heroData['title'] ?? 'L\'Art du<br><span class="highlight">Sur-Mesure</span><br>Africain' !!}
                </h1>
                <p class="hero-description">
                    {{ $heroData['description'] ?? 'Notre atelier réunit les meilleurs artisans pour créer des pièces uniques adaptées à vos envies. Tradition, qualité et personnalisation au service de votre style.' }}
                </p>
                <div class="hero-cta">
                    <a href="{{ route('frontend.contact') }}" class="btn-primary-hero">
                        <i class="fas fa-calendar-alt"></i> Prendre rendez-vous
                    </a>
                    <a href="#services" class="btn-outline-hero">
                        <i class="fas fa-info-circle"></i> Nos services
                    </a>
                </div>
            </div>
            <div class="hero-video">
                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800" alt="Atelier">
                <button class="play-btn"><i class="fas fa-play"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- PROCESS -->
<section class="process-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Notre Processus</span>
            <h2 class="section-title">Comment ça marche ?</h2>
            <p class="section-subtitle">Un accompagnement personnalisé de A à Z pour votre création sur-mesure</p>
        </div>
        
        <div class="process-grid">
            <div class="process-step">
                <div class="step-number">1</div>
                <h3>Consultation</h3>
                <p>Échangez avec notre styliste pour définir vos envies et votre budget.</p>
            </div>
            <div class="process-step">
                <div class="step-number">2</div>
                <h3>Design</h3>
                <p>Création de croquis et sélection des tissus selon vos préférences.</p>
        </div>
            <div class="process-step">
                <div class="step-number">3</div>
                <h3>Confection</h3>
                <p>Nos artisans réalisent votre pièce avec un savoir-faire traditionnel.</p>
            </div>
            <div class="process-step">
                <div class="step-number">4</div>
                <h3>Livraison</h3>
                <p>Essayage final et ajustements pour une coupe parfaite.</p>
                    </div>
                    </div>
                </div>
</section>

<!-- GALLERY -->
<section class="gallery-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Galerie</span>
            <h2 class="section-title">Dans nos ateliers</h2>
                </div>

        <div class="gallery-grid">
            <div class="gallery-item large">
                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600" alt="Atelier">
                <div class="gallery-overlay"><span>Atelier de couture</span></div>
                    </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400" alt="Tissus">
                <div class="gallery-overlay"><span>Sélection des tissus</span></div>
                    </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400" alt="Création">
                <div class="gallery-overlay"><span>Travail manuel</span></div>
                </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400" alt="Finitions">
                <div class="gallery-overlay"><span>Finitions soignées</span></div>
                    </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?w=400" alt="Résultat">
                <div class="gallery-overlay"><span>Résultat final</span></div>
            </div>
        </div>
    </div>
</section>

<!-- SERVICES -->
<section class="services-section" id="services">
    <div class="container">
        <div class="services-header">
            <span class="section-tag">Services</span>
            <h2 class="section-title">Nos prestations</h2>
            <p class="section-subtitle">Des services sur-mesure pour tous vos besoins</p>
        </div>
        
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-ruler"></i></div>
                <h3>Création Sur-Mesure</h3>
                <p>Une pièce unique créée selon vos envies et vos mesures exactes.</p>
                <div class="service-price">À partir de 150€</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-edit"></i></div>
                <h3>Personnalisation</h3>
                <p>Adaptez une pièce existante avec vos modifications personnelles.</p>
                <div class="service-price">À partir de 50€</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-cut"></i></div>
                <h3>Retouches</h3>
                <p>Ajustements et retouches pour une coupe parfaite.</p>
                <div class="service-price">À partir de 25€</div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIAL -->
<section class="testimonial-section">
    <div class="container">
        <div class="testimonial-content">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text">
                "J'ai fait réaliser ma robe de mariage traditionnelle par l'atelier RACINE. 
                Le résultat a dépassé toutes mes attentes. Un travail d'une qualité exceptionnelle 
                et une équipe à l'écoute."
            </p>
            <div class="testimonial-author">
                <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=100&h=100&fit=crop&crop=faces" alt="Cliente">
                <div class="author-info">
                    <h4>Marie-Claire Diop</h4>
                    <span>Mariée en Juin 2024</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Créons ensemble votre pièce unique</h2>
        <p>Prenez rendez-vous avec notre équipe pour discuter de votre projet.</p>
        <a href="{{ route('frontend.contact') }}" class="btn-cta">
            <i class="fas fa-calendar-check"></i> Réserver une consultation
        </a>
    </div>
</section>
@endsection
