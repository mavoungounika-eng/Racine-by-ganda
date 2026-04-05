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
                    <div class="product-card">
                        <a href="{{ route('frontend.product', $product->id) }}" class="product-image-link">
                            <div class="product-image">
                                <img src="{{ $product->main_image ?? $product->image ?? 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=500&fit=crop' }}" 
                                     alt="{{ $product->title ?? $product->name ?? 'Produit' }}"
                                     loading="lazy">
                                <div class="product-badges">
                                    {{-- Badge Type Vendeur --}}
                                    @if($product->isBrand())
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white text-[10px] font-bold uppercase tracking-wide shadow-md">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            RACINE BY GANDA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#F8F6F3] border-2 border-[#8B5A2B] text-[#8B5A2B] text-[10px] font-semibold uppercase tracking-wide">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                            Créateur partenaire
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
                    <div class="product-card">
                        <a href="#" class="product-image-link">
                            <div class="product-image">
                                <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=400&h=500&fit=crop" alt="Produit">
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
                        {{ $products->links('pagination::bootstrap-4') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
    
    <!-- CMS INTRO SECTION (si disponible) -->
    @if(isset($introSection) && $introSection && $introSection->is_active)
    <section class="shop-intro-section" style="padding: 3rem 0; background: white;">
        <div class="container">
            @php
                $introData = $introSection->data ?? [];
            @endphp
            @if($introData['title'] ?? false)
            <h2 class="text-center mb-3" style="font-family: 'Cormorant Garamond', serif; color: #2C1810;">{{ $introData['title'] }}</h2>
            @endif
            @if($introData['content'] ?? false)
            <div class="text-center" style="max-width: 800px; margin: 0 auto; color: #8B7355;">
                {!! $introData['content'] !!}
            </div>
            @endif
        </div>
    </section>
    @endif
    
    <!-- CMS FOOTER SECTION (si disponible) -->
    @if(isset($footerSection) && $footerSection && $footerSection->is_active)
    <section class="shop-footer-section" style="padding: 2rem 0; background: #F8F6F3;">
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
@endsection

{{-- JavaScript extrait vers public/js/frontend-shop.js --}}
