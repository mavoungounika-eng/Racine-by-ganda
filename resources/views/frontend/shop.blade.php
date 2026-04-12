@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Boutique - RACINE BY GANDA')
{{-- CSS extrait vers public/css/frontend-shop.css --}}

@section('content')
<!-- HERO -->
<section class="shop-hero">
    <div class="container">
        <nav class="breadcrumb-custom mb-3">
            <a href="{{ route('frontend.home') }}">Accueil</a>
            <span class="mx-2">/</span>
            <span>Boutique</span>
        </nav>
        @php
            $heroData = $heroSection?->data ?? [];
            $introData = $introSection?->data ?? [];
        @endphp
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Notre Boutique' }}</h1>
        <p>{{ $heroData['description'] ?? $introData['description'] ?? 'Découvrez nos créations uniques inspirées du patrimoine africain' }}</p>
        @if($heroData['badge'] ?? false)
        <span class="hero-badge">{{ $heroData['badge'] }}</span>
        @endif
    </div>
</section>

<!-- SHOP CONTENT -->
<section class="shop-content">
    <div class="container">
        <div class="shop-grid">
            <!-- SIDEBAR FILTERS -->
            <aside class="filters-sidebar">
                <form method="GET" action="{{ route('frontend.shop') }}" id="shop-filters-form">
                    @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    
                    <div class="filter-section">
                        <h3 class="filter-title">
                            Catégories
                            <i class="fas fa-chevron-down"></i>
                        </h3>
                        <div class="filter-options">
                            @forelse($categories ?? [] as $category)
                            <label class="filter-checkbox">
                                <input type="checkbox" name="category[]" value="{{ $category->id }}"
                                       {{ in_array($category->id, (array)request('category', [])) ? 'checked' : '' }}>
                                <span>{{ $category->name }}</span>
                                <span class="count">({{ $category->products_count ?? 0 }})</span>
                            </label>
                            @empty
                            <p class="text-muted small">Aucune catégorie disponible</p>
                            @endforelse
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3 class="filter-title">
                            Prix
                            <i class="fas fa-chevron-down"></i>
                        </h3>
                        <div class="price-range">
                            <input type="number" class="price-input" name="price_min" 
                                   placeholder="Min" min="0" value="{{ request('price_min') }}">
                            <span>-</span>
                            <input type="number" class="price-input" name="price_max" 
                                   placeholder="Max" value="{{ request('price_max') }}">
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3 class="filter-title">
                            Stock
                            <i class="fas fa-chevron-down"></i>
                        </h3>
                        <div class="filter-options">
                            <label class="filter-radio">
                                <input type="radio" name="stock_filter" value="in_stock"
                                       {{ request('stock_filter') === 'in_stock' ? 'checked' : '' }}>
                                <span>En stock</span>
                            </label>
                            <label class="filter-radio">
                                <input type="radio" name="stock_filter" value="low_stock"
                                       {{ request('stock_filter') === 'low_stock' ? 'checked' : '' }}>
                                <span>Stock faible</span>
                            </label>
                            <label class="filter-radio">
                                <input type="radio" name="stock_filter" value=""
                                       {{ !request('stock_filter') ? 'checked' : '' }}>
                                <span>Tous</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3 class="filter-title">
                            Taille
                            <i class="fas fa-chevron-down"></i>
                        </h3>
                        <div class="filter-options">
                            <label class="filter-checkbox">
                                <input type="checkbox" name="size[]" value="xs">
                                <span>XS</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="size[]" value="s">
                                <span>S</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="size[]" value="m">
                                <span>M</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="size[]" value="l">
                                <span>L</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="size[]" value="xl">
                                <span>XL</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3 class="filter-title">
                            Couleur
                            <i class="fas fa-chevron-down"></i>
                        </h3>
                        <div class="filter-options">
                            <label class="filter-checkbox">
                                <input type="checkbox" name="color[]" value="multicolore">
                                <span>Multicolore</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="color[]" value="rouge">
                                <span>Rouge</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="color[]" value="bleu">
                                <span>Bleu</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="color[]" value="vert">
                                <span>Vert</span>
                            </label>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="color[]" value="jaune">
                                <span>Jaune</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-apply-filter">
                        <i class="fas fa-filter me-2"></i> Appliquer les filtres
                    </button>
                    <a href="{{ route('frontend.shop') }}" class="btn-reset-filter">
                        <i class="fas fa-undo me-2"></i> Réinitialiser
                    </a>
                </form>
            </aside>
            
            <!-- PRODUCTS AREA -->
            <div class="products-area">
                <div class="products-toolbar">
                    <span class="results-count">
                        <strong>{{ $products->total() }}</strong> produit(s) trouvé(s)
                        @if(request()->hasAny(['category', 'search', 'price_min', 'price_max', 'stock_filter']))
                        <a href="{{ route('frontend.shop') }}" class="clear-filters-link">
                            <i class="fas fa-times"></i> Effacer les filtres
                        </a>
                        @endif
                    </span>
                    <div class="toolbar-actions">
                        <form method="GET" action="{{ route('frontend.shop') }}" class="sort-form">
                            @foreach(request()->except('sort', 'page') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="latest" {{ request('sort') === 'latest' || !request('sort') ? 'selected' : '' }}>Trier par : Nouveautés</option>
                                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Prix croissant</option>
                                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Prix décroissant</option>
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Nom (A-Z)</option>
                                <option value="stock" {{ request('sort') === 'stock' ? 'selected' : '' }}>Stock disponible</option>
                            </select>
                        </form>
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="view-btn" data-view="list">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="products-grid" id="productsGrid">
                    @forelse($products ?? [] as $product)
                    <div class="product-card reveal-item">
                        <a href="{{ route('frontend.product', $product->id) }}" class="product-image-link">
                            <div class="product-image">
                                @if($product->main_image ?? $product->image ?? false)
                                <img src="{{ $product->main_image ?? $product->image }}"
                                     alt="{{ $product->title ?? $product->name ?? 'Produit' }}"
                                     loading="lazy">
                                @else
                                <div class="product-css-placeholder"><i class="fas fa-tshirt"></i></div>
                                @endif
                                <div class="product-badges">
                                    {{-- Badge Type Vendeur --}}
                                    @if($product->isBrand())
                                        <span class="badge-brand">
                                            <i class="fas fa-star"></i> RACINE BY GANDA
                                        </span>
                                    @else
                                        <span class="badge-creator">
                                            <i class="fas fa-user"></i> Créateur partenaire
                                        </span>
                                    @endif
                                    
                                    {{-- Badges Stock/Promo --}}
                                    @if(($product->stock ?? 0) <= 0)
                                    <span class="badge-out-of-stock">Stock épuisé</span>
                                    @else
                                        @if($product->is_new ?? false)
                                        <span class="badge-new">Nouveau</span>
                                        @endif
                                        @if(isset($product->original_price) && $product->original_price > $product->price)
                                        <span class="badge-sale">-{{ round((1 - $product->price / $product->original_price) * 100) }}%</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="product-actions">
                                    @auth
                                    <form action="{{ route('profile.wishlist.toggle') }}" method="POST" class="wishlist-toggle-form d-inline">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <button type="submit" class="action-btn wishlist-btn" 
                                                title="Favoris"
                                                data-product-id="{{ $product->id }}"
                                                data-is-in-wishlist="{{ $product->isInWishlist(Auth::id()) ? 'true' : 'false' }}">
                                            <i class="{{ $product->isInWishlist(Auth::id()) ? 'fas' : 'far' }} fa-heart"></i>
                                        </button>
                                    </form>
                                    @endauth
                                    <a href="{{ route('frontend.product', $product->id) }}" class="action-btn" title="Aperçu"><i class="far fa-eye"></i></a>
                                </div>
                            </div>
                        </a>
                        <div class="product-info">
                            <a href="{{ route('frontend.product', $product->id) }}" class="product-info-link">
                                <div class="product-category">{{ $product->category->name ?? 'Mode' }}</div>
                                <h3 class="product-name">{{ $product->title ?? $product->name ?? 'Produit' }}</h3>
                                <div class="product-price">
                                    <span class="current-price">{{ number_format($product->price, 2) }} €</span>
                                    @if(isset($product->original_price) && $product->original_price > $product->price)
                                    <span class="original-price">{{ number_format($product->original_price, 2) }} €</span>
                                    @endif
                                </div>
                            </a>
                            @if(($product->stock ?? 0) > 0)
                            <form action="{{ route('cart.add') }}" method="POST" class="quick-add-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect" value="shop">
                                <button type="submit" class="quick-add">
                                    <i class="fas fa-shopping-bag me-2"></i> Ajouter au panier
                                </button>
                            </form>
                            @else
                            <div class="quick-add-form">
                                <button type="button" class="quick-add" disabled style="opacity: 0.6; cursor: not-allowed;">
                                    <i class="fas fa-ban me-2"></i> Stock épuisé
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <!-- Demo products if no data -->
                    @for($i = 0; $i < 9; $i++)
                    <div class="product-card reveal-item">
                        <a href="#" class="product-image-link">
                            <div class="product-image">
                                <div class="product-css-placeholder"><i class="fas fa-tshirt"></i></div>
                                <div class="product-badges">
                                    @if($i % 5 === 0)
                                    <span class="badge-out-of-stock">Stock épuisé</span>
                                    @else
                                        @if($i % 3 === 0)
                                        <span class="badge-new">Nouveau</span>
                                        @endif
                                        @if($i % 4 === 0)
                                        <span class="badge-sale">-20%</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="product-actions">
                                    @auth
                                    <form action="{{ route('profile.wishlist.toggle') }}" method="POST" class="wishlist-toggle-form d-inline">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $i + 1 }}">
                                        <button type="submit" class="action-btn wishlist-btn" title="Favoris">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </form>
                                    @endauth
                                    <a href="{{ route('frontend.product', $i + 1) }}" class="action-btn" title="Aperçu"><i class="far fa-eye"></i></a>
                                </div>
                            </div>
                        </a>
                        <div class="product-info">
                            <a href="#" class="product-info-link">
                                <div class="product-category">Mode Africaine</div>
                                <h3 class="product-name">Robe Wax Élégante Collection {{ $i + 1 }}</h3>
                                <div class="product-price">
                                    <span class="current-price">{{ 79 + ($i * 10) }},00 €</span>
                                    @if($i % 4 === 0)
                                    <span class="original-price">{{ 99 + ($i * 10) }},00 €</span>
                                    @endif
                                </div>
                            </a>
                            @if($i % 5 !== 0)
                            <form action="{{ route('cart.add') }}" method="POST" class="quick-add-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $i + 1 }}">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect" value="shop">
                                <button type="submit" class="quick-add">
                                    <i class="fas fa-shopping-bag me-2"></i> Ajouter au panier
                                </button>
                            </form>
                            @else
                            <div class="quick-add-form">
                                <button type="button" class="quick-add" disabled style="opacity: 0.6; cursor: not-allowed;">
                                    <i class="fas fa-ban me-2"></i> Stock épuisé
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endfor
                    @endforelse
                </div>
                
                <!-- PAGINATION -->
                @if($products->hasPages())
                <div class="pagination-wrapper">
                    <div class="pagination">
                        {{ $products->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
    
    <!-- CMS INTRO SECTION (si disponible) -->
    @if(isset($introSection) && $introSection && $introSection->is_active)
    <section class="shop-intro-section" style="padding: 3rem 0; background: #FFFFFF;">
        <div class="container">
            @php
                $introData = $introSection->data ?? [];
            @endphp
            @if($introData['title'] ?? false)
            <h2 class="text-center mb-3" style="font-family: 'Aleppo', 'Aileron', serif; color: #160D0C;">{{ $introData['title'] }}</h2>
            @endif
            @if($introData['content'] ?? false)
            <div class="text-center" style="max-width: 800px; margin: 0 auto; color: #160D0C;">
                {!! $introData['content'] !!}
            </div>
            @endif
        </div>
    </section>
    @endif
    
    <!-- CMS FOOTER SECTION (si disponible) -->
    @if(isset($footerSection) && $footerSection && $footerSection->is_active)
    <section class="shop-footer-section" style="padding: 2rem 0; background: #FFFFFF;">
        <div class="container">
            @php
                $footerData = $footerSection->data ?? [];
            @endphp
            @if($footerData['content'] ?? false)
            <div class="text-center">
                {!! $footerData['content'] !!}
            </div>
            @endif
        </div>
    </section>
    @endif

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => 'Boutique', 'url' => null],
    ],
    'backUrl' => route('frontend.home'),
    'backText' => 'Retour à l\'accueil',
    'position' => 'bottom',
])
@push('scripts')
<script>
(function () {
    const items = document.querySelectorAll('.reveal-item');
    if (!items.length) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => entry.target.classList.add('revealed'), i * 60);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    items.forEach(el => observer.observe(el));
})();
</script>
@endpush
@endsection

{{-- JavaScript extrait vers public/js/frontend-shop.js --}}
