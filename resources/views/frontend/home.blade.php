@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'RACINE BY GANDA - Mode Africaine Contemporaine')

@push('styles')
<style>
    /* ===== HERO SECTION ===== */
    .hero {
        min-height: 100vh;
        display: flex;
        align-items: center;
        position: relative;
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        overflow: hidden;
    }
    
    .hero-bg-pattern {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4A574' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
        padding: 2rem 0;
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
        margin-bottom: 2rem;
    }
    
    .hero-badge::before {
        content: '';
        width: 8px; height: 8px;
        background: #D4A574;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }
    
    .hero-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 4.5rem;
        font-weight: 600;
        line-height: 1.1;
        margin-bottom: 1.5rem;
    }
    
    .hero-title .highlight {
        color: #D4A574;
        position: relative;
    }
    
    .hero-description {
        font-size: 1.15rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 2.5rem;
        max-width: 500px;
    }
    
    .hero-cta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .btn-primary-custom {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        background: linear-gradient(135deg, #D4A574 0%, #B8956A 100%);
        color: #1a1a1a;
        padding: 1rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(212, 165, 116, 0.3);
        color: #1a1a1a;
    }
    
    .btn-outline-custom {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 1rem 2rem;
        border-radius: 50px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-outline-custom:hover {
        background: white;
        color: #1a1a1a;
        border-color: white;
    }
    
    .hero-image {
        position: relative;
    }
    
    .hero-image-main {
        width: 100%;
        height: 600px;
        object-fit: cover;
        border-radius: 24px;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
    }
    
    .hero-image-float {
        position: absolute;
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        animation: float 4s ease-in-out infinite;
    }
    
    .hero-float-1 {
        bottom: 10%;
        left: -50px;
        animation-delay: 0s;
    }
    
    .hero-float-2 {
        top: 10%;
        right: -30px;
        animation-delay: 1s;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    
    .hero-float-content {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .hero-float-icon {
        width: 48px; height: 48px;
        background: linear-gradient(135deg, #D4A574 0%, #B8956A 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
    }
    
    .hero-float-text h4 {
        font-size: 0.9rem;
        color: #1a1a1a;
        margin: 0;
    }
    
    .hero-float-text span {
        font-size: 0.8rem;
        color: #888;
    }
    
    /* ===== FEATURES BAR ===== */
    .features-bar {
        background: #F8F6F3;
        padding: 2rem 0;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .feature-icon {
        width: 50px; height: 50px;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #D4A574;
        font-size: 1.25rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .feature-text h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2C1810;
        margin: 0 0 0.25rem 0;
    }
    
    .feature-text span {
        font-size: 0.85rem;
        color: #8B7355;
    }
    
    /* ===== CATEGORIES SECTION ===== */
    .categories-section {
        padding: 6rem 0;
        background: white;
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
        font-size: 3rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 1rem;
    }
    
    .section-subtitle {
        font-size: 1.1rem;
        color: #8B7355;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
    
    .category-card {
        position: relative;
        height: 400px;
        border-radius: 20px;
        overflow: hidden;
        text-decoration: none;
        transition: transform 0.4s;
    }
    
    .category-card:hover {
        transform: translateY(-10px);
    }
    
    .category-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s;
    }
    
    .category-card:hover img {
        transform: scale(1.1);
    }
    
    .category-overlay {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 2rem;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
        color: white;
    }
    
    .category-overlay h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .category-overlay span {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    /* ===== FEATURED PRODUCTS ===== */
    .products-section {
        padding: 6rem 0;
        background: #F8F6F3;
    }
    
    .products-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 3rem;
    }
    
    .view-all-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #8B5A2B;
        font-weight: 600;
        text-decoration: none;
        transition: gap 0.3s;
    }
    
    .view-all-link:hover {
        gap: 1rem;
        color: #6B4423;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }
    
    .product-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
    }
    
    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
    }
    
    .product-image {
        position: relative;
        height: 280px;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.08);
    }
    
    .product-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: #D4A574;
        color: white;
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .product-wishlist {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 40px; height: 40px;
        background: white;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .product-card:hover .product-wishlist {
        opacity: 1;
        transform: translateY(0);
    }
    
    .product-wishlist:hover {
        background: #D4A574;
        color: white;
    }
    
    .product-info {
        padding: 1.5rem;
    }
    
    .product-category {
        font-size: 0.8rem;
        color: #8B7355;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
    }
    
    .product-name {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .product-price {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .product-price .current {
        font-size: 1.25rem;
        font-weight: 700;
        color: #8B5A2B;
    }
    
    .product-price .original {
        font-size: 1rem;
        color: #aaa;
        text-decoration: line-through;
    }
    
    /* ===== ABOUT SECTION ===== */
    .about-section {
        padding: 6rem 0;
        background: #2C1810;
        color: white;
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
        border-radius: 16px;
        overflow: hidden;
    }
    
    .about-img:first-child {
        grid-row: span 2;
        height: 100%;
    }
    
    .about-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .about-content .section-tag {
        background: rgba(212, 165, 116, 0.2);
    }
    
    .about-content .section-title {
        color: white;
    }
    
    .about-text {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
        line-height: 1.8;
        margin-bottom: 2rem;
    }
    
    .about-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .stat-item h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 600;
        color: #D4A574;
        margin-bottom: 0.5rem;
    }
    
    .stat-item span {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.6);
    }
    
    /* ===== CREATORS SECTION ===== */
    .creators-section {
        padding: 6rem 0;
        background: white;
    }
    
    .creators-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .creator-card {
        background: #F8F6F3;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s;
    }
    
    .creator-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .creator-avatar {
        width: 100px; height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1.5rem;
        border: 4px solid white;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .creator-card h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .creator-specialty {
        color: #8B5A2B;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    
    .creator-bio {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* ===== NEWSLETTER SECTION - SUPPRIMÉE ===== */
    /* Les styles newsletter ont été supprimés - Remplacés par les CTA premium dans le layout frontend */
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .hero-content { grid-template-columns: 1fr; text-align: center; }
        .hero-image { display: none; }
        .hero-title { font-size: 3rem; }
        .hero-description { margin: 0 auto 2rem; }
        .hero-cta { justify-content: center; }
        .features-grid { grid-template-columns: repeat(2, 1fr); }
        .categories-grid { grid-template-columns: repeat(2, 1fr); }
        .products-grid { grid-template-columns: repeat(2, 1fr); }
        .about-grid { grid-template-columns: 1fr; }
        .creators-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; }
        .section-title { font-size: 2rem; }
        .features-grid { grid-template-columns: 1fr; }
        .categories-grid { grid-template-columns: 1fr; }
        .products-grid { grid-template-columns: 1fr; }
        /* Responsive newsletter supprimé */
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-bg-pattern"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                @php
                    $heroSection = $cmsPage?->section('hero');
                    $heroData = $heroSection?->data ?? [];
                @endphp
                <span class="hero-badge">{{ $heroData['badge'] ?? 'Nouvelle Collection 2025' }}</span>
                <h1 class="hero-title">
                    {!! $heroData['title'] ?? "L'Élégance<br><span class=\"highlight\">Africaine</span><br>Réinventée" !!}
                </h1>
                <p class="hero-description">
                    {{ $heroData['description'] ?? "Découvrez des créations uniques qui célèbrent notre héritage. Des pièces artisanales confectionnées par les meilleurs créateurs africains." }}
                </p>
                <div class="hero-cta">
                    <a href="{{ route('frontend.shop') }}" class="btn-primary-custom">
                        <i class="fas fa-shopping-bag"></i>
                        Explorer la boutique
                    </a>
                    <a href="{{ route('frontend.creators') }}" class="btn-outline-custom">
                        <i class="fas fa-palette"></i>
                        Nos créateurs
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=800&h=1000&fit=crop" alt="Mode Africaine" class="hero-image-main">
                <div class="hero-image-float hero-float-1">
                    <div class="hero-float-content">
                        <div class="hero-float-icon"><i class="fas fa-truck"></i></div>
                        <div class="hero-float-text">
                            <h4>Livraison Express</h4>
                            <span>Partout en France</span>
                        </div>
                    </div>
                </div>
                <div class="hero-image-float hero-float-2">
                    <div class="hero-float-content">
                        <div class="hero-float-icon"><i class="fas fa-award"></i></div>
                        <div class="hero-float-text">
                            <h4>100% Authentique</h4>
                            <span>Fait main en Afrique</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES BAR -->
<section class="features-bar">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-truck"></i></div>
                <div class="feature-text">
                    <h4>Livraison Gratuite</h4>
                    <span>Dès 100€ d'achat</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="feature-text">
                    <h4>Paiement Sécurisé</h4>
                    <span>CB, PayPal, Stripe</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-rotate-left"></i></div>
                <div class="feature-text">
                    <h4>Retours Faciles</h4>
                    <span>Sous 30 jours</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <div class="feature-text">
                    <h4>Support 7j/7</h4>
                    <span>À votre écoute</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Collections</span>
            <h2 class="section-title">Explorez nos univers</h2>
            <p class="section-subtitle">Des vêtements traditionnels aux accessoires modernes, trouvez votre style</p>
        </div>
        
        <div class="categories-grid">
            @foreach($categories ?? [] as $category)
            <a href="{{ route('frontend.shop', ['category' => $category->id]) }}" class="category-card">
                @if($category->image)
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}">
                @else
                    <img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400" alt="{{ $category->name }}">
                @endif
                <div class="category-overlay">
                    <h3>{{ $category->name }}</h3>
                    <span>{{ $category->products_count ?? 0 }} article{{ $category->products_count > 1 ? 's' : '' }}</span>
                </div>
            </a>
            @endforeach
            
            @if(empty($categories) || count($categories ?? []) === 0)
            <a href="{{ route('frontend.shop') }}" class="category-card">
                <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=500&fit=crop" alt="Robes">
                <div class="category-overlay">
                    <h3>Robes</h3>
                    <span>Découvrir</span>
                </div>
            </a>
            <a href="{{ route('frontend.shop') }}" class="category-card">
                <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=400&h=500&fit=crop" alt="Chemises">
                <div class="category-overlay">
                    <h3>Chemises</h3>
                    <span>Découvrir</span>
                </div>
            </a>
            <a href="{{ route('frontend.shop') }}" class="category-card">
                <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=500&fit=crop" alt="Accessoires">
                <div class="category-overlay">
                    <h3>Accessoires</h3>
                    <span>Découvrir</span>
                </div>
            </a>
            <a href="{{ route('frontend.shop') }}" class="category-card">
                <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=400&h=500&fit=crop" alt="Sur-mesure">
                <div class="category-overlay">
                    <h3>Sur-mesure</h3>
                    <span>Découvrir</span>
                </div>
            </a>
            @endif
        </div>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="products-section">
    <div class="container">
        <div class="products-header">
            <div>
                <span class="section-tag">Tendances</span>
                <h2 class="section-title">Nos coups de cœur</h2>
            </div>
            <a href="{{ route('frontend.shop') }}" class="view-all-link">
                Voir tout <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="products-grid">
            @foreach($featuredProducts ?? [] as $product)
            <a href="{{ route('frontend.product', $product->id) }}" class="product-card">
                <div class="product-image">
                    @if($product->main_image)
                        <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->title }}">
                    @else
                        <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=500&fit=crop" alt="{{ $product->title }}">
                    @endif
                    @if($product->created_at->isAfter(now()->subDays(30)))
                    <span class="product-badge">Nouveau</span>
                    @endif
                    @auth
                    <button class="product-wishlist" 
                            data-product-id="{{ $product->id }}"
                            onclick="event.preventDefault(); toggleWishlist({{ $product->id }});">
                        <i class="far fa-heart" id="wishlist-icon-{{ $product->id }}"></i>
                    </button>
                    @endauth
                </div>
                <div class="product-info">
                    <div class="product-category">{{ $product->category->name ?? 'Mode' }}</div>
                    <h3 class="product-name">{{ $product->title }}</h3>
                    <div class="product-price">
                        <span class="current">{{ number_format($product->price, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </a>
            @endforeach
            
            @if(empty($featuredProducts) || count($featuredProducts ?? []) === 0)
            @for($i = 0; $i < 4; $i++)
            <a href="{{ route('frontend.shop') }}" class="product-card">
                <div class="product-image">
                    <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=400&h=500&fit=crop" alt="Produit">
                    <span class="product-badge">Nouveau</span>
                </div>
                <div class="product-info">
                    <div class="product-category">Mode</div>
                    <h3 class="product-name">Découvrir nos produits</h3>
                    <div class="product-price">
                        <span class="current">Voir la boutique</span>
                    </div>
                </div>
            </a>
            @endfor
            @endif
        </div>
    </div>
</section>

<!-- ABOUT SECTION -->
<section class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-images">
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=600&h=800&fit=crop" alt="Mode Africaine">
                </div>
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=400&h=500&fit=crop" alt="Création">
                </div>
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=400&h=500&fit=crop" alt="Vêtement">
                </div>
            </div>
            <div class="about-content">
                <span class="section-tag">Notre Histoire</span>
                <h2 class="section-title">L'Art de la Mode Africaine</h2>
                <p class="about-text">
                    RACINE BY GANDA est née d'une passion pour l'artisanat africain et le désir de 
                    connecter les talents du continent avec le monde. Chaque pièce raconte une histoire, 
                    celle d'un créateur, d'un savoir-faire ancestral sublimé par une vision contemporaine.
                </p>
                <p class="about-text">
                    Nous collaborons avec plus de 50 artisans et créateurs à travers l'Afrique, 
                    garantissant des conditions de travail équitables et la préservation des techniques traditionnelles.
                </p>
                <div class="about-stats">
                    <div class="stat-item">
                        <h3>50+</h3>
                        <span>Créateurs partenaires</span>
                    </div>
                    <div class="stat-item">
                        <h3>15</h3>
                        <span>Pays représentés</span>
                    </div>
                    <div class="stat-item">
                        <h3>5000+</h3>
                        <span>Clients satisfaits</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CREATORS SECTION -->
<section class="creators-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Artisans</span>
            <h2 class="section-title">Nos Créateurs</h2>
            <p class="section-subtitle">Découvrez les talents derrière nos créations uniques</p>
        </div>
        
        <div class="creators-grid">
            <div class="creator-card">
                <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&h=200&fit=crop&crop=faces" alt="Créateur" class="creator-avatar">
                <h3>Amina Diallo</h3>
                <p class="creator-specialty">Styliste - Dakar, Sénégal</p>
                <p class="creator-bio">Spécialiste du wax moderne, Amina crée des pièces qui allient tradition et contemporanéité.</p>
            </div>
            <div class="creator-card">
                <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=200&h=200&fit=crop&crop=faces" alt="Créateur" class="creator-avatar">
                <h3>Kwame Asante</h3>
                <p class="creator-specialty">Créateur - Accra, Ghana</p>
                <p class="creator-bio">Expert en kente, Kwame perpétue un savoir-faire familial vieux de trois générations.</p>
            </div>
            <div class="creator-card">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&h=200&fit=crop&crop=faces" alt="Créateur" class="creator-avatar">
                <h3>Fatou Ndiaye</h3>
                <p class="creator-specialty">Accessoiriste - Abidjan, Côte d'Ivoire</p>
                <p class="creator-bio">Créatrice de bijoux et accessoires inspirés des motifs traditionnels ivoiriens.</p>
            </div>
        </div>
    </div>
</section>

{{-- Section newsletter supprimée - Remplacée par les CTA dans le footer --}}
@push('scripts')
<script>
// Fonction pour gérer la wishlist
function toggleWishlist(productId) {
    const icon = document.getElementById('wishlist-icon-' + productId);
    if (!icon) return;
    
    fetch('{{ route("profile.wishlist.toggle") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.is_in_wishlist) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = '#ED5F1E';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '';
            }
        } else {
            if (data.requires_auth || response.status === 401) {
                window.location.href = '{{ route("login") }}';
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}
</script>
@endpush

@endsection
