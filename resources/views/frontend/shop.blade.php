@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Boutique - RACINE BY GANDA')

@push('styles')
<style>
    .shop-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 4rem 0;
        margin-top: -70px;
        padding-top: calc(4rem + 70px);
    }
    
    .shop-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .shop-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
    }
    
    .hero-badge {
        display: inline-block;
        background: rgba(212, 165, 116, 0.2);
        color: #D4A574;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1rem;
    }
    
    .clear-filters-link {
        color: #8B7355;
        text-decoration: none;
        font-size: 0.9rem;
        margin-left: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .clear-filters-link:hover {
        color: #D4A574;
        text-decoration: underline;
    }
    
    .sort-form {
        display: inline-block;
    }
    
    .filter-radio {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0;
        cursor: pointer;
    }
    
    .filter-radio input[type="radio"] {
        margin: 0;
    }
    
    .breadcrumb-custom {
        background: none;
        padding: 0;
        margin: 0;
    }
    
    .breadcrumb-custom a {
        color: #D4A574;
        text-decoration: none;
    }
    
    .breadcrumb-custom span {
        color: rgba(255, 255, 255, 0.5);
    }
    
    .shop-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .shop-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2rem;
    }
    
    /* SIDEBAR FILTERS */
    .filters-sidebar {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        height: fit-content;
        position: sticky;
        top: 100px;
    }
    
    .filter-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .filter-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .filter-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    
    .filter-title i {
        font-size: 0.8rem;
        color: #8B7355;
        transition: transform 0.3s;
    }
    
    .filter-title.collapsed i {
        transform: rotate(-90deg);
    }
    
    .filter-options {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .filter-checkbox {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
    }
    
    .filter-checkbox input {
        width: 18px;
        height: 18px;
        accent-color: #8B5A2B;
    }
    
    .filter-checkbox span {
        color: #5C4A3D;
        font-size: 0.95rem;
    }
    
    .filter-checkbox .count {
        color: #aaa;
        font-size: 0.85rem;
        margin-left: auto;
    }
    
    /* Price Range */
    .price-range {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .price-input {
        flex: 1;
        padding: 0.75rem;
        border: 1.5px solid #E5DDD3;
        border-radius: 8px;
        font-size: 0.95rem;
        text-align: center;
    }
    
    .price-input:focus {
        outline: none;
        border-color: #D4A574;
    }
    
    .btn-apply-filter {
        width: 100%;
        padding: 0.75rem;
        background: #2C1810;
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: all 0.3s;
    }
    
    .btn-apply-filter:hover {
        background: #8B5A2B;
    }
    
    .btn-reset-filter {
        width: 100%;
        padding: 0.75rem;
        background: transparent;
        color: #8B7355;
        border: 1.5px solid #E5DDD3;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
        margin-top: 0.75rem;
        transition: all 0.3s;
    }
    
    .btn-reset-filter:hover {
        border-color: #8B5A2B;
        color: #8B5A2B;
    }
    
    /* PRODUCTS AREA */
    .products-area {
        display: flex;
        flex-direction: column;
    }
    
    .products-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
    }
    
    .results-count {
        color: #5C4A3D;
        font-size: 0.95rem;
    }
    
    .results-count strong {
        color: #2C1810;
    }
    
    .toolbar-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .sort-select {
        padding: 0.6rem 1rem;
        border: 1.5px solid #E5DDD3;
        border-radius: 8px;
        background: white;
        font-size: 0.9rem;
        color: #5C4A3D;
        cursor: pointer;
    }
    
    .view-toggle {
        display: flex;
        gap: 0.5rem;
    }
    
    .view-btn {
        width: 40px;
        height: 40px;
        border: 1.5px solid #E5DDD3;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #8B7355;
        transition: all 0.3s;
    }
    
    .view-btn.active, .view-btn:hover {
        background: #2C1810;
        color: white;
        border-color: #2C1810;
    }
    
    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    .products-grid.list-view {
        grid-template-columns: 1fr;
    }
    
    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
    }
    
    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    }
    
    .product-image {
        position: relative;
        height: 300px;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.08);
    }
    
    .product-badges {
        position: absolute;
        top: 1rem;
        left: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .badge-new {
        background: #D4A574;
        color: white;
        padding: 0.3rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-sale {
        background: #E53E3E;
        color: white;
        padding: 0.3rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-out-of-stock {
        background: #6B7280;
        color: white;
        padding: 0.3rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-actions {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        opacity: 0;
        transform: translateX(10px);
        transition: all 0.3s;
    }
    
    .product-card:hover .product-actions {
        opacity: 1;
        transform: translateX(0);
    }
    
    .action-btn {
        width: 40px;
        height: 40px;
        background: white;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }
    
    .action-btn:hover {
        background: #D4A574;
        color: white;
    }
    
    .product-info {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
    }
    
    .product-info-link {
        display: block;
        text-decoration: none;
        color: inherit;
        flex: 1;
    }
    
    .product-info-link:hover {
        text-decoration: none;
        color: inherit;
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
        font-size: 1.2rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
        line-height: 1.3;
    }
    
    .product-price {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .current-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #8B5A2B;
    }
    
    .original-price {
        font-size: 1rem;
        color: #aaa;
        text-decoration: line-through;
    }
    
    /* Quick Add Button */
    .quick-add-form {
        margin-top: 1rem;
        padding: 0;
    }
    
    .quick-add {
        width: 100%;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        padding: 0.85rem 1.25rem;
        text-align: center;
        font-weight: 600;
        cursor: pointer;
        border: none;
        border-radius: 10px;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .quick-add:hover {
        background: linear-gradient(135deg, #1a0f09 0%, #2C1810 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(44, 24, 16, 0.3);
    }
    
    .quick-add:active {
        transform: translateY(0);
    }
    
    .product-image-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    
    /* Pagination */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 3rem;
    }
    
    .pagination {
        display: flex;
        gap: 0.5rem;
    }
    
    .page-link {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1.5px solid #E5DDD3;
        border-radius: 10px;
        color: #5C4A3D;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .page-link:hover, .page-link.active {
        background: #2C1810;
        color: white;
        border-color: #2C1810;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #E5DDD3;
        margin-bottom: 1.5rem;
    }
    
    .empty-state h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.75rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #8B7355;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .shop-grid {
            grid-template-columns: 1fr;
        }
        
        .filters-sidebar {
            position: static;
        }
        
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .shop-hero h1 {
            font-size: 2.5rem;
        }
        
        .products-grid {
            grid-template-columns: 1fr;
        }
        
        .products-toolbar {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
    }
</style>
@endpush

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const grid = document.getElementById('productsGrid');
            if(this.dataset.view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        });
    });
    
    // Filter collapse
    document.querySelectorAll('.filter-title').forEach(title => {
        title.addEventListener('click', function() {
            this.classList.toggle('collapsed');
            const options = this.nextElementSibling;
            if(options) {
                options.style.display = this.classList.contains('collapsed') ? 'none' : 'flex';
            }
        });
    });
    
    // AJAX - Ajout au panier avec mise à jour temps réel
    document.querySelectorAll('.quick-add-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Désactiver le bouton pendant la requête
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Ajout...';
            
            fetch('{{ route("cart.add") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le compteur panier
                    updateCartCount(data.count);
                    
                    // Afficher notification de succès
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Produit ajouté au panier !', 'success');
                    } else {
                        // Fallback : notification simple
                        console.log('✅ ' + (data.message || 'Produit ajouté au panier !'));
                    }
                } else {
                    // Afficher erreur
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Erreur lors de l\'ajout au panier', 'error');
                    } else {
                        alert(data.message || 'Erreur lors de l\'ajout au panier');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                if (typeof showNotification === 'function') {
                    showNotification('Erreur lors de l\'ajout au panier. Veuillez réessayer.', 'error');
                }
            })
            .finally(() => {
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    });
    
    // Fonction utilitaire pour mettre à jour le compteur panier
    function updateCartCount(count) {
        // Mettre à jour tous les éléments de compteur panier
        const cartBadge = document.getElementById('cart-count-badge');
        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'flex' : 'none';
            // Animation
            cartBadge.style.transform = 'scale(1.2)';
            cartBadge.style.transition = 'transform 0.3s';
            setTimeout(() => {
                cartBadge.style.transform = 'scale(1)';
            }, 300);
        }
        
        // Mettre à jour autres sélecteurs possibles
        document.querySelectorAll('#cart-count, .cart-count').forEach(el => {
            if (el) {
                el.textContent = count;
            }
        });
    }
    
    // Wishlist toggle
    document.querySelectorAll('.wishlist-toggle-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('.wishlist-btn');
            const icon = button.querySelector('i');
            
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle icon
                    if (data.is_in_wishlist) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        button.style.color = '#DC2626';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        button.style.color = '';
                    }
                    
                    // Show notification
                    if (typeof showNotification === 'function') {
                        showNotification(data.message, 'success');
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Erreur', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showNotification === 'function') {
                    showNotification('Erreur lors de l\'ajout aux favoris', 'error');
                }
            });
        });
    });
});
</script>
@endpush
