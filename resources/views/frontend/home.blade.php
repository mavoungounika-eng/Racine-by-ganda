@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'RACINE BY GANDA - Mode Africaine Contemporaine')

@section('content')
<!-- HERO SECTION -->
@if(!empty($cmsHeroBanners) && $cmsHeroBanners->count())
    <x-cms.banner-slider :banners="$cmsHeroBanners" :autoplay="true" />
@else
    <section class="hero">
        <div class="hero-bg-pattern"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <span class="hero-badge">Nouvelle Collection 2025</span>
                    <h1 class="hero-title">
                        L'Élégance<br><span class="highlight">Africaine</span><br>Réinventée
                    </h1>
                    <p class="hero-description">
                        Découvrez des créations uniques qui célèbrent notre héritage. Des pièces artisanales confectionnées par les meilleurs créateurs africains.
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
@endif

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

@php
  $introBlock = $cmsBlocks['home_intro'] ?? null;
  $ctaBlock   = $cmsBlocks['home_cta']   ?? null;
@endphp

@if($introBlock && $introBlock->is_active)
  <section class="home-intro">
    <div class="container">
      {!! $introBlock->content !!}
    </div>
  </section>
@else
    <!-- ABOUT SECTION (Fallback) -->
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
@endif

@if($ctaBlock && $ctaBlock->is_active)
  <section class="home-cta">
    <div class="container">
      {!! $ctaBlock->content !!}
    </div>
  </section>
@endif

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
