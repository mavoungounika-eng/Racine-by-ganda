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
    <div class="hero-slider" id="heroSlider">
        <div class="hero-slider-track">
            @foreach(range(1, 7) as $i)
            <div class="hero-slide {{ $i === 1 ? 'active' : '' }}">
                <img src="{{ asset('storage/hero/hero-' . sprintf('%02d', $i) . '.jpeg') }}"
                     alt="Racine by Ganda - Look {{ $i }}"
                     loading="{{ $i === 1 ? 'eager' : 'lazy' }}">
            </div>
            @endforeach
        </div>

        <div class="hero-slider-dots">
            @foreach(range(1, 7) as $i)
            <button class="hero-dot {{ $i === 1 ? 'active' : '' }}"
                    data-index="{{ $i - 1 }}"
                    aria-label="Slide {{ $i }}"></button>
            @endforeach
        </div>

        <button class="hero-slider-prev" aria-label="Précédent">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hero-slider-next" aria-label="Suivant">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
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
            <a href="{{ route('frontend.shop', ['category' => $category->id]) }}" class="category-card category-card-css">
                @if($category->image)
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}"
                         style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                @else
                    <div class="category-css-bg">
                        <div class="category-css-pattern"></div>
                        <i class="fas fa-tshirt category-css-icon"></i>
                    </div>
                @endif
                <div class="category-overlay">
                    <h3>{{ $category->name }}</h3>
                    <span>{{ $category->products_count ?? 0 }} article{{ ($category->products_count ?? 0) > 1 ? 's' : '' }}</span>
                </div>
            </a>
            @endforeach
            
            @if(empty($categories) || count($categories ?? []) === 0)
            @php
                $defaultCategories = [
                    ['name' => 'Robes', 'icon' => 'fas fa-star', 'mod' => ''],
                    ['name' => 'Chemises', 'icon' => 'fas fa-tshirt', 'mod' => '--accent'],
                    ['name' => 'Accessoires', 'icon' => 'fas fa-gem', 'mod' => '--dark'],
                    ['name' => 'Sur-mesure', 'icon' => 'fas fa-cut', 'mod' => '--warm'],
                ];
            @endphp
            @foreach($defaultCategories as $cat)
            <a href="{{ route('frontend.shop') }}" class="category-card category-card-css{{ $cat['mod'] }}">
                <div class="category-css-bg">
                    <div class="category-css-pattern"></div>
                    <i class="{{ $cat['icon'] }} category-css-icon"></i>
                </div>
                <div class="category-overlay">
                    <h3>{{ $cat['name'] }}</h3>
                    <span>Découvrir <i class="fas fa-arrow-right ms-1"></i></span>
                </div>
            </a>
            @endforeach
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
            <a href="{{ route('frontend.product', $product->id) }}" class="product-card reveal-item">
                <div class="product-image">
                    @if($product->main_image)
                        <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->title }}">
                    @else
                        <div class="product-css-placeholder"><i class="fas fa-tshirt"></i></div>
                    @endif
                    @if($product->created_at->isAfter(now()->subDays(30)))
                    <span class="product-badge">Nouveau</span>
                    @endif
                    <div class="product-hover-cta">
                        <span><i class="fas fa-eye me-2"></i>Voir le produit</span>
                    </div>
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
            @php
                $demoProducts = [
                    ['title' => 'Robe Wax Premium', 'category' => 'Robes', 'price' => '45 000', 'mod' => ''],
                    ['title' => 'Ensemble Kente', 'category' => 'Ensembles', 'price' => '62 000', 'mod' => '--b'],
                    ['title' => 'Collier Artisanal', 'category' => 'Accessoires', 'price' => '18 500', 'mod' => '--c'],
                    ['title' => 'Chemise Bogolan', 'category' => 'Chemises', 'price' => '28 000', 'mod' => '--d'],
                ];
            @endphp
            @foreach($demoProducts as $demo)
            <a href="{{ route('frontend.shop') }}" class="product-card reveal-item">
                <div class="product-image product-image-css{{ $demo['mod'] }}">
                    <div class="product-css-placeholder">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <span class="product-badge">Nouveau</span>
                    <div class="product-hover-cta">
                        <span><i class="fas fa-eye me-2"></i>Voir le produit</span>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category">{{ $demo['category'] }}</div>
                    <h3 class="product-name">{{ $demo['title'] }}</h3>
                    <div class="product-price">
                        <span class="current">{{ $demo['price'] }} FCFA</span>
                    </div>
                </div>
            </a>
            @endforeach
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
                    <div class="about-img about-img--primary">
                        <div class="about-img-css about-img-css--main">
                            <div class="about-img-pattern"></div>
                            <span class="about-img-text">Mode<br>Africaine</span>
                        </div>
                    </div>
                    <div class="about-img">
                        <div class="about-img-css about-img-css--accent">
                            <i class="fas fa-palette"></i>
                        </div>
                    </div>
                    <div class="about-img">
                        <div class="about-img-css about-img-css--dark">
                            <i class="fas fa-gem"></i>
                        </div>
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
            @forelse($latestCreators ?? [] as $creator)
            <div class="creator-card reveal-item">
                @if($creator->user?->avatar)
                    <img src="{{ asset('storage/' . $creator->user->avatar) }}"
                         alt="{{ $creator->user->name }}"
                         class="creator-avatar">
                @else
                    <div class="creator-avatar creator-avatar-initials">
                        {{ strtoupper(substr($creator->user?->name ?? 'R', 0, 1)) }}
                    </div>
                @endif
                <h3>{{ $creator->user?->name ?? 'Créateur' }}</h3>
                <p class="creator-specialty">
                    {{ $creator->specialty ?? 'Styliste' }}
                    @if($creator->city)· {{ $creator->city }}@endif
                </p>
                <p class="creator-bio">{{ Str::limit($creator->bio ?? 'Créateur passionné de mode africaine authentique.', 120) }}</p>
                <a href="{{ route('frontend.creators') }}" class="creator-link">
                    Voir les créations <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            @empty
            @php
                $demoCreators = [
                    ['initials' => 'A', 'name' => 'Amina Diallo', 'specialty' => 'Styliste · Dakar', 'bio' => 'Spécialiste du wax moderne, crée des pièces qui allient tradition et contemporanéité.'],
                    ['initials' => 'K', 'name' => 'Kwame Asante', 'specialty' => 'Créateur · Accra', 'bio' => 'Expert en kente, perpétue un savoir-faire familial vieux de trois générations.'],
                    ['initials' => 'F', 'name' => 'Fatou Ndiaye', 'specialty' => 'Accessoiriste · Abidjan', 'bio' => 'Bijoux et accessoires inspirés des motifs traditionnels ivoiriens.'],
                ];
            @endphp
            @foreach($demoCreators as $demo)
            <div class="creator-card reveal-item">
                <div class="creator-avatar creator-avatar-initials">{{ $demo['initials'] }}</div>
                <h3>{{ $demo['name'] }}</h3>
                <p class="creator-specialty">{{ $demo['specialty'] }}</p>
                <p class="creator-bio">{{ $demo['bio'] }}</p>
                <a href="{{ route('frontend.creators') }}" class="creator-link">
                    Voir les créations <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>
</section>

{{-- Section newsletter supprimée - Remplacée par les CTA dans le footer --}}
@push('scripts')
<script nonce="{{ csp_nonce() }}">
// Hero Slider
(function () {
    const slider = document.getElementById('heroSlider');
    if (!slider) return;
    const slides = slider.querySelectorAll('.hero-slide');
    const dots   = slider.querySelectorAll('.hero-dot');
    let current  = 0, timer;
    function goTo(i) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (i + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }
    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }
    function startAuto() { timer = setInterval(next, 4500); }
    function stopAuto()  { clearInterval(timer); }
    slider.querySelector('.hero-slider-next').addEventListener('click', () => { stopAuto(); next(); startAuto(); });
    slider.querySelector('.hero-slider-prev').addEventListener('click', () => { stopAuto(); prev(); startAuto(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { stopAuto(); goTo(i); startAuto(); }));
    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);
    startAuto();
})();

// Scroll reveal géré dans layouts/frontend.blade.php (consolidé)

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
