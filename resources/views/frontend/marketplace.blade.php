@extends('layouts.frontend')

@section('title', 'Marketplace - RACINE BY GANDA')

@push('styles')
<style>
    .marketplace-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0 3rem;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
    }
    
    .marketplace-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .marketplace-hero p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .marketplace-stats {
        display: flex;
        gap: 3rem;
        justify-content: center;
        margin-top: 2rem;
    }
    
    .marketplace-stats .stat {
        text-align: center;
    }
    
    .marketplace-stats .number {
        display: block;
        font-size: 2.5rem;
        font-weight: 700;
        color: #D4A574;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .marketplace-stats .label {
        display: block;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 0.5rem;
    }
    
    .marketplace-filters {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }
    
    .filters-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 1rem;
    }
    
    .search-box {
        position: relative;
    }
    
    .search-box input {
        width: 100%;
        padding: 0.75rem 1rem;
        padding-right: 3rem;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 0.95rem;
    }
    
    .search-box i {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #8B7355;
    }
    
    .filter-select {
        padding: 0.75rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 0.95rem;
        background: white;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }
    
    
    .product-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(44, 24, 16, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(212, 165, 116, 0.1);
    }
    
    .product-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 60px rgba(44, 24, 16, 0.15);
        border-color: rgba(212, 165, 116, 0.3);
    }
    
    .product-image {
        position: relative;
        padding-top: 125%;
        background: linear-gradient(135deg, #F8F6F3 0%, #E5DDD3 100%);
        overflow: hidden;
    }
    
    .product-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.08);
    }
    
    .product-image::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 0%, rgba(44, 24, 16, 0.05) 100%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .product-card:hover .product-image::after {
        opacity: 1;
    }
    
    .creator-badge {
        position: absolute;
        bottom: 1rem;
        left: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        padding: 0.75rem 1rem;
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        border: 1px solid rgba(212, 165, 116, 0.2);
        transform: translateY(100%);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .product-card:hover .creator-badge {
        transform: translateY(0);
        opacity: 1;
    }
    
    .creator-badge img {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #D4A574;
    }
    
    .creator-badge span {
        color: #2C1810;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .product-badges {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        z-index: 10;
    }
    
    .badge {
        padding: 0.5rem 0.9rem;
        border-radius: 25px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .badge-new {
        background: linear-gradient(135deg, #ED5F1E 0%, #FF7A3D 100%);
        color: white;
    }
    
    .badge-limited {
        background: linear-gradient(135deg, #FFB800 0%, #FFC933 100%);
        color: #2C1810;
    }
    
    .product-info {
        padding: 1.75rem;
        background: white;
    }
    
    .product-info h3 {
        font-size: 1.15rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
        line-height: 1.4;
        font-family: 'Cormorant Garamond', serif;
        transition: color 0.3s ease;
    }
    
    .product-card:hover .product-info h3 {
        color: #ED5F1E;
    }
    
    .product-price {
        font-size: 1.4rem;
        font-weight: 700;
        background: linear-gradient(135deg, #D4A574 0%, #B8935F 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1.25rem;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .product-actions {
        display: flex;
        gap: 0.75rem;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .product-card:hover .product-actions {
        opacity: 1;
        transform: translateY(0);
    }
    
    .btn-view {
        flex: 1;
        padding: 0.9rem;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        text-align: center;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(44, 24, 16, 0.2);
    }
    
    .btn-view:hover {
        background: linear-gradient(135deg, #ED5F1E 0%, #FF7A3D 100%);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.3);
    }
    
    .btn-cart {
        padding: 0.9rem 1.1rem;
        background: linear-gradient(135deg, #D4A574 0%, #B8935F 100%);
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(212, 165, 116, 0.3);
    }
    
    .btn-cart:hover {
        background: linear-gradient(135deg, #ED5F1E 0%, #FF7A3D 100%);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
    }
    
    .pagination-wrapper {
        margin-top: 4rem;
        padding: 2rem 0;
    }
    
    .pagination-premium {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem 0;
    }
    
    .pagination-premium .page-item {
        list-style: none;
    }
    
    .pagination-premium .page-link-prev,
    .pagination-premium .page-link-next,
    .pagination-premium .page-link-number {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        color: #2C1810;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(44, 24, 16, 0.08);
    }
    
    .pagination-premium .page-link-number {
        min-width: 45px;
        justify-content: center;
        padding: 0.75rem 1rem;
    }
    
    .pagination-premium .page-link-prev:hover,
    .pagination-premium .page-link-next:hover,
    .pagination-premium .page-link-number:hover {
        background: linear-gradient(135deg, #ED5F1E 0%, #FF7A3D 100%);
        color: white;
        border-color: #ED5F1E;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(237, 95, 30, 0.3);
    }
    
    .pagination-premium .page-item.active .page-link-number {
        background: linear-gradient(135deg, #D4A574 0%, #B8935F 100%);
        color: white;
        border-color: #D4A574;
        box-shadow: 0 4px 15px rgba(212, 165, 116, 0.4);
    }
    
    .pagination-premium .page-item.disabled .page-link-prev,
    .pagination-premium .page-item.disabled .page-link-next {
        background: rgba(255, 255, 255, 0.5);
        color: #8B7355;
        border-color: rgba(212, 165, 116, 0.1);
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .pagination-info {
        text-align: center;
        color: #8B7355;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        grid-column: 1 / -1;
    }
    
    .empty-state i {
        color: #D4A574;
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #8B7355;
    }
    
    @media (max-width: 1200px) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .filters-row {
            grid-template-columns: 1fr;
        }
        
        .marketplace-stats {
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .pagination-premium .page-link-prev span,
        .pagination-premium .page-link-next span {
            display: none;
        }
        
        .pagination-premium {
            gap: 0.25rem;
        }
        
        .pagination-premium .page-link-prev,
        .pagination-premium .page-link-next,
        .pagination-premium .page-link-number {
            padding: 0.6rem 0.8rem;
            min-width: 40px;
        }
    }
</style>
@endpush

@section('content')
{{-- Hero Section --}}
<section class="marketplace-hero text-center">
    <div class="container">
        <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill mb-4" 
             style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);">
            <i class="fas fa-shopping-bag text-white"></i>
            <span class="text-white font-weight-bold text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Marketplace</span>
        </div>
        
        <h1>{{ $cmsPage?->title ?? 'Marketplace Créateurs' }}</h1>
        <p>{{ $cmsPage?->meta_description ?? 'Découvrez les créations uniques de nos stylistes partenaires' }}</p>
        
        {{-- Stats --}}
        <div class="marketplace-stats">
            <div class="stat">
                <span class="number">{{ $products->total() }}</span>
                <span class="label">Produits disponibles</span>
            </div>
            <div class="stat">
                <span class="number">{{ $creatorsCount }}</span>
                <span class="label">Créateurs partenaires</span>
            </div>
        </div>
        
        {{-- CTA Nos Créateurs --}}
        <div class="mt-4">
            <a href="{{ route('frontend.creators') }}" class="btn btn-outline-light btn-lg">
                <i class="fas fa-users mr-2"></i>
                Découvrir nos créateurs
            </a>
        </div>
    </div>
</section>

{{-- Filtres & Produits --}}
<section class="py-5" style="background: #F8F6F3;">
    <div class="container">
        {{-- Filtres --}}
        <form method="GET" action="{{ route('frontend.marketplace') }}" class="marketplace-filters">
            <div class="filters-row">
                {{-- Recherche --}}
                <div class="search-box">
                    <input type="search" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Rechercher un produit..."
                           class="form-control">
                    <i class="fas fa-search"></i>
                </div>
                
                {{-- Filtre créateur --}}
                <select name="creator" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous les créateurs</option>
                    @foreach($creators as $creator)
                    <option value="{{ $creator->id }}" {{ request('creator') == $creator->id ? 'selected' : '' }}>
                        {{ $creator->creatorProfile->brand_name ?? $creator->name }}
                    </option>
                    @endforeach
                </select>
                
                {{-- Filtre catégorie --}}
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
                
                {{-- Tri --}}
                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Plus récents</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Plus populaires</option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Prix croissant</option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Prix décroissant</option>
                </select>
            </div>
        </form>
        
        {{-- Compteur --}}
        <div class="mb-4">
            <h2 class="h5 text-dark font-weight-bold">
                {{ $products->total() }} produit(s) trouvé(s)
            </h2>
        </div>
        
        {{-- Grille Produits --}}
        <div class="products-grid">
            @forelse($products as $product)
            <div class="product-card">
                {{-- Image --}}
                <div class="product-image">
                    <img src="{{ isset($product->mainImage) ? $product->mainImage->url : ($product->main_image ? Storage::url($product->main_image) : asset('images/placeholder-product.jpg')) }}" 
                         alt="{{ $product->title }}">
                    
                    {{-- Badge créateur --}}
                    @if($product->creator && $product->creator->creatorProfile)
                    <div class="creator-badge">
                        <img src="{{ Storage::url($product->creator->creatorProfile->logo_path) }}" 
                             alt="{{ $product->creator->creatorProfile->brand_name }}">
                        <span>{{ $product->creator->creatorProfile->brand_name }}</span>
                    </div>
                    @endif
                    
                    {{-- Badges produit --}}
                    <div class="product-badges">
                        @if($product->created_at->diffInDays(now()) < 7)
                        <span class="badge badge-new">Nouveau</span>
                        @endif
                        @if($product->stock > 0 && $product->stock <= 5)
                        <span class="badge badge-limited">Stock limité</span>
                        @endif
                    </div>
                </div>
                
                {{-- Info --}}
                <div class="product-info">
                    <h3>{{ Str::limit($product->title, 50) }}</h3>
                    <p class="product-price">{{ number_format($product->price, 0, ',', ' ') }} FCFA</p>
                    
                    {{-- Actions --}}
                    <div class="product-actions">
                        <a href="{{ route('frontend.product', $product->id) }}" class="btn-view">
                            Voir détails
                        </a>
                        <button class="btn-cart" data-product="{{ $product->id }}">
                            <i class="fas fa-shopping-bag"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="fas fa-shopping-bag fa-4x"></i>
                <h3>Aucun produit trouvé</h3>
                <p>Essayez de modifier vos filtres ou <a href="{{ route('frontend.marketplace') }}">voir tous les produits</a></p>
            </div>
            @endforelse
        </div>
        
        
        {{-- Pagination Premium --}}
        @if($products->hasPages())
        <div class="pagination-wrapper">
            <nav aria-label="Navigation des produits">
                <ul class="pagination-premium">
                    {{-- Bouton Précédent --}}
                    @if ($products->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link-prev">
                                <i class="fas fa-chevron-left"></i>
                                <span class="d-none d-md-inline">Précédent</span>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a href="{{ $products->previousPageUrl() }}" class="page-link-prev">
                                <i class="fas fa-chevron-left"></i>
                                <span class="d-none d-md-inline">Précédent</span>
                            </a>
                        </li>
                    @endif

                    {{-- Numéros de page --}}
                    @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        @if ($page == $products->currentPage())
                            <li class="page-item active">
                                <span class="page-link-number">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a href="{{ $url }}" class="page-link-number">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach

                    {{-- Bouton Suivant --}}
                    @if ($products->hasMorePages())
                        <li class="page-item">
                            <a href="{{ $products->nextPageUrl() }}" class="page-link-next">
                                <span class="d-none d-md-inline">Suivant</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link-next">
                                <span class="d-none d-md-inline">Suivant</span>
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </li>
                    @endif
                </ul>
            </nav>
            
            {{-- Info pagination --}}
            <div class="pagination-info">
                Affichage de {{ $products->firstItem() ?? 0 }} à {{ $products->lastItem() ?? 0 }} sur {{ $products->total() }} produits
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
