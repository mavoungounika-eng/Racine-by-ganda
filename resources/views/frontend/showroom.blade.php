@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Showroom - RACINE BY GANDA')

@push('styles')
<style>
    .showroom-hero {
        min-height: 70vh;
        background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.98) 100%),
                    url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1600') center/cover;
        display: flex;
        align-items: center;
        margin-top: -70px;
        padding-top: 70px;
    }
    
    .hero-content {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
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
        justify-content: center;
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
    
    /* INFO BAR */
    .info-bar {
        background: #D4A574;
        padding: 1.5rem 0;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        text-align: center;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: white;
    }
    
    .info-item i {
        font-size: 1.5rem;
    }
    
    .info-item span {
        font-weight: 500;
    }
    
    /* ABOUT SHOWROOM */
    .about-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .about-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }
    
    .about-images {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .about-img {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }
    
    .about-img:first-child {
        grid-row: span 2;
    }
    
    .about-img img {
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
    
    .about-text p {
        color: #5C4A3D;
        font-size: 1.1rem;
        line-height: 1.9;
        margin-bottom: 1.5rem;
    }
    
    .features-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
    }
    
    .features-list li {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 0;
        color: #5C4A3D;
    }
    
    .features-list i {
        color: #D4A574;
        font-size: 1.25rem;
    }
    
    /* COLLECTIONS */
    .collections-section {
        padding: 6rem 0;
        background: white;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .collections-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .collection-card {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        height: 450px;
    }
    
    .collection-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .collection-card:hover img {
        transform: scale(1.1);
    }
    
    .collection-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(44, 24, 16, 0.9) 0%, transparent 60%);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 2rem;
        color: white;
    }
    
    .collection-tag {
        display: inline-block;
        background: #D4A574;
        color: white;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        width: fit-content;
    }
    
    .collection-overlay h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .collection-overlay p {
        opacity: 0.8;
        font-size: 0.95rem;
    }
    
    /* VISIT INFO */
    .visit-section {
        padding: 6rem 0;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
    }
    
    .visit-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }
    
    .visit-content {
        color: white;
    }
    
    .visit-content .section-tag {
        background: rgba(212, 165, 116, 0.2);
    }
    
    .visit-content .section-title {
        color: white;
    }
    
    .visit-content p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
        line-height: 1.8;
        margin-bottom: 2rem;
    }
    
    .visit-info {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .visit-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .visit-icon {
        width: 50px;
        height: 50px;
        background: rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #D4A574;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .visit-item h4 {
        color: white;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .visit-item span {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .btn-book {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 2rem;
        transition: all 0.3s;
    }
    
    .btn-book:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(212, 165, 116, 0.3);
        color: white;
    }
    
    .visit-map {
        border-radius: 24px;
        overflow: hidden;
        height: 400px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }
    
    .visit-map iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    /* FAQ */
    .faq-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .faq-grid {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .faq-item {
        background: white;
        border-radius: 16px;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    
    .faq-question {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .faq-question:hover {
        background: #F8F6F3;
    }
    
    .faq-question h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
    }
    
    .faq-question i {
        color: #D4A574;
        transition: transform 0.3s;
    }
    
    .faq-answer {
        padding: 0 1.5rem 1.5rem;
        color: #8B7355;
        line-height: 1.7;
        display: none;
    }
    
    .faq-item.active .faq-answer {
        display: block;
    }
    
    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }
    
    @media (max-width: 1024px) {
        .hero-title { font-size: 3rem; }
        .about-grid, .visit-grid { grid-template-columns: 1fr; }
        .collections-grid { grid-template-columns: repeat(2, 1fr); }
        .info-grid { grid-template-columns: 1fr; gap: 1rem; }
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; }
        .collections-grid { grid-template-columns: 1fr; }
        .hero-cta { flex-direction: column; align-items: center; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="showroom-hero">
    <div class="container">
        <div class="hero-content">
            @php
                $heroSection = $cmsPage?->section('hero');
                $heroData = $heroSection?->data ?? [];
            @endphp
            <span class="hero-badge">{{ $heroData['badge'] ?? '📍 Paris, France' }}</span>
            <h1 class="hero-title">
                {!! $heroData['title'] ?? 'Notre <span class="highlight">Showroom</span>' !!}
            </h1>
            <p class="hero-description">
                {{ $heroData['description'] ?? 'Découvrez nos collections dans un espace dédié à l\'élégance africaine. Essayez, touchez et laissez-vous inspirer par nos créations uniques.' }}
            </p>
            <div class="hero-cta">
                <a href="{{ route('frontend.contact') }}" class="btn-primary-hero">
                    <i class="fas fa-calendar-alt"></i> Prendre rendez-vous
                </a>
            </div>
        </div>
    </div>
</section>

<!-- INFO BAR -->
<section class="info-bar">
    <div class="container">
        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>15 Rue de la Mode, 75003 Paris</span>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span>Mar-Sam : 10h - 19h</span>
            </div>
            <div class="info-item">
                <i class="fas fa-calendar-check"></i>
                <span>Sur rendez-vous uniquement</span>
            </div>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-images">
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600" alt="Showroom">
                </div>
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400" alt="Collection">
                </div>
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400" alt="Tissus">
                </div>
            </div>
            <div class="about-content">
                <span class="section-tag">L'Expérience</span>
                <h2 class="section-title">Un espace unique dédié à la mode africaine</h2>
                <div class="about-text">
                    <p>
                        Notre showroom parisien est bien plus qu'un simple espace de vente. 
                        C'est un lieu de rencontre entre cultures, où chaque visite devient 
                        une expérience immersive dans l'univers de la mode africaine.
                    </p>
                    <p>
                        Sur plus de 200m², découvrez nos collections permanentes et éphémères, 
                        conseillé par notre équipe passionnée.
                    </p>
                </div>
                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> Conseils personnalisés par nos stylistes</li>
                    <li><i class="fas fa-check-circle"></i> Essayage privé sur rendez-vous</li>
                    <li><i class="fas fa-check-circle"></i> Service de retouches sur place</li>
                    <li><i class="fas fa-check-circle"></i> Collections exclusives showroom</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- COLLECTIONS -->
<section class="collections-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">En Vitrine</span>
            <h2 class="section-title">Collections actuelles</h2>
        </div>
        
        <div class="collections-grid">
            <div class="collection-card">
                <img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=600" alt="Collection">
                <div class="collection-overlay">
                    <span class="collection-tag">Nouveau</span>
                    <h3>Printemps 2025</h3>
                    <p>La nouvelle collection aux couleurs vives</p>
                </div>
            </div>
            <div class="collection-card">
                <img src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600" alt="Collection">
                <div class="collection-overlay">
                    <span class="collection-tag">Exclusif</span>
                    <h3>Haute Couture</h3>
                    <p>Pièces uniques sur commande</p>
                </div>
            </div>
            <div class="collection-card">
                <img src="https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=600" alt="Collection">
                <div class="collection-overlay">
                    <span class="collection-tag">Best-seller</span>
                    <h3>Accessoires</h3>
                    <p>Bijoux et maroquinerie artisanale</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VISIT INFO -->
<section class="visit-section">
    <div class="container">
        <div class="visit-grid">
            <div class="visit-content">
                <span class="section-tag">Nous Rendre Visite</span>
                <h2 class="section-title">Planifiez votre venue</h2>
                <p>
                    Pour vous garantir une expérience personnalisée et de qualité, 
                    les visites se font uniquement sur rendez-vous. Notre équipe vous 
                    accueillera avec plaisir pour vous faire découvrir nos collections.
                </p>
                
                <div class="visit-info">
                    <div class="visit-item">
                        <div class="visit-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h4>Adresse</h4>
                            <span>15 Rue de la Mode, 75003 Paris</span>
                        </div>
                    </div>
                    <div class="visit-item">
                        <div class="visit-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <h4>Horaires</h4>
                            <span>Mardi au Samedi, 10h - 19h</span>
                        </div>
                    </div>
                    <div class="visit-item">
                        <div class="visit-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h4>Téléphone</h4>
                            <span>+33 1 23 45 67 89</span>
                        </div>
                    </div>
                </div>
                
                <a href="{{ route('frontend.contact') }}" class="btn-book">
                    <i class="fas fa-calendar-check"></i> Réserver un créneau
                </a>
            </div>
            
            <div class="visit-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.3522!3d48.8566!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDjCsDUxJzIzLjgiTiAywrAyMScwNy45IkU!5e0!3m2!1sfr!2sfr!4v1234567890" allowfullscreen loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">FAQ</span>
            <h2 class="section-title">Questions fréquentes</h2>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item active">
                <div class="faq-question">
                    <h4>Dois-je prendre rendez-vous ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Oui, nous fonctionnons uniquement sur rendez-vous afin de vous garantir une expérience personnalisée et un service de qualité. Vous pouvez réserver en ligne ou par téléphone.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Peut-on acheter directement sur place ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Absolument ! Vous pouvez essayer et acheter nos créations directement au showroom. Nous acceptons les cartes bancaires et le paiement en plusieurs fois.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Proposez-vous des services de retouches ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Oui, notre atelier peut réaliser des retouches et ajustements sur place. Comptez généralement 24 à 48h pour les modifications simples.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Y a-t-il un parking à proximité ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Plusieurs parkings publics se trouvent à moins de 5 minutes à pied. Le showroom est également accessible en métro (ligne 8, station Filles du Calvaire).
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function() {
        const item = this.parentElement;
        const wasActive = item.classList.contains('active');
        
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        
        if (!wasActive) {
            item.classList.add('active');
        }
    });
});
</script>
@endpush
