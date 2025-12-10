@extends('layouts.frontend')

@section('title', ($product->name ?? 'Produit') . ' - RACINE BY GANDA')

@push('styles')
<style>
    .product-page {
        padding: 2rem 0 4rem;
        background: #F8F6F3;
    }
    
    .breadcrumb-custom {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
        font-size: 0.9rem;
    }
    
    .breadcrumb-custom a {
        color: #8B7355;
        text-decoration: none;
    }
    
    .breadcrumb-custom a:hover {
        color: #8B5A2B;
    }
    
    .breadcrumb-custom span {
        color: #ccc;
    }
    
    .breadcrumb-custom .current {
        color: #2C1810;
        font-weight: 500;
    }
    
    .product-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        background: white;
        border-radius: 24px;
        overflow: hidden;
    }
    
    /* GALLERY */
    .product-gallery {
        padding: 2rem;
    }
    
    .main-image {
        width: 100%;
        height: 500px;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 1rem;
        position: relative;
    }
    
    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .main-image:hover img {
        transform: scale(1.05);
    }
    
    .zoom-btn {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
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
    
    .zoom-btn:hover {
        background: #D4A574;
        color: white;
    }
    
    .thumbnail-gallery {
        display: flex;
        gap: 0.75rem;
    }
    
    .thumbnail {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s;
    }
    
    .thumbnail.active, .thumbnail:hover {
        border-color: #D4A574;
    }
    
    .thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* PRODUCT DETAILS */
    .product-details {
        padding: 2rem;
        display: flex;
        flex-direction: column;
    }
    
    .product-badges {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    
    .badge {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .badge-new {
        background: rgba(212, 165, 116, 0.15);
        color: #8B5A2B;
    }
    
    .badge-stock {
        background: rgba(34, 197, 94, 0.15);
        color: #16A34A;
    }
    
    .badge-limited {
        background: rgba(239, 68, 68, 0.15);
        color: #DC2626;
    }
    
    .product-category {
        color: #8B7355;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 0.5rem;
    }
    
    .product-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 600;
        color: #2C1810;
        line-height: 1.2;
        margin-bottom: 1rem;
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .stars {
        display: flex;
        gap: 0.25rem;
        color: #FFB800;
    }
    
    .rating-text {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    .product-price {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .current-price {
        font-size: 2rem;
        font-weight: 700;
        color: #8B5A2B;
    }
    
    .original-price {
        font-size: 1.25rem;
        color: #aaa;
        text-decoration: line-through;
    }
    
    .discount-badge {
        background: #E53E3E;
        color: white;
        padding: 0.3rem 0.75rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .product-description {
        color: #5C4A3D;
        line-height: 1.8;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #E5DDD3;
    }
    
    /* OPTIONS */
    .product-options {
        margin-bottom: 2rem;
    }
    
    .option-group {
        margin-bottom: 1.5rem;
    }
    
    .option-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    
    .option-label span {
        font-weight: 600;
        color: #2C1810;
    }
    
    .option-label a {
        color: #8B5A2B;
        font-size: 0.9rem;
        text-decoration: none;
    }
    
    .size-options {
        display: flex;
        gap: 0.75rem;
    }
    
    .size-btn {
        min-width: 50px;
        height: 50px;
        border: 2px solid #E5DDD3;
        background: white;
        border-radius: 10px;
        font-weight: 600;
        color: #5C4A3D;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .size-btn:hover, .size-btn.active {
        border-color: #2C1810;
        background: #2C1810;
        color: white;
    }
    
    .size-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    
    .color-options {
        display: flex;
        gap: 0.75rem;
    }
    
    .color-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 3px solid transparent;
        cursor: pointer;
        position: relative;
        transition: all 0.3s;
    }
    
    .color-btn::after {
        content: '';
        position: absolute;
        inset: -6px;
        border: 2px solid transparent;
        border-radius: 50%;
        transition: border-color 0.3s;
    }
    
    .color-btn:hover::after, .color-btn.active::after {
        border-color: #2C1810;
    }
    
    /* QUANTITY */
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .qty-control {
        display: flex;
        align-items: center;
        border: 2px solid #E5DDD3;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .qty-btn {
        width: 45px;
        height: 45px;
        border: none;
        background: #F8F6F3;
        font-size: 1.25rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .qty-btn:hover {
        background: #E5DDD3;
    }
    
    .qty-input {
        width: 60px;
        height: 45px;
        border: none;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .qty-input:focus {
        outline: none;
    }
    
    /* ACTIONS */
    .product-actions {
        display: flex;
        gap: 1rem;
        margin-top: auto;
    }
    
    .btn-add-cart {
        flex: 1;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        transition: all 0.3s;
    }
    
    .btn-add-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 40px rgba(44, 24, 16, 0.3);
    }
    
    .btn-wishlist {
        width: 55px;
        height: 55px;
        border: 2px solid #E5DDD3;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.25rem;
        color: #8B7355;
        transition: all 0.3s;
    }
    
    .btn-wishlist:hover {
        border-color: #D4A574;
        color: #D4A574;
    }
    
    .btn-wishlist.active {
        background: #D4A574;
        border-color: #D4A574;
        color: white;
    }
    
    /* EXTRA INFO */
    .product-extras {
        display: flex;
        gap: 2rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .extra-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #5C4A3D;
        font-size: 0.9rem;
    }
    
    .extra-item i {
        color: #D4A574;
    }
    
    /* TABS */
    .product-tabs {
        margin-top: 3rem;
    }
    
    .tabs-header {
        display: flex;
        gap: 2rem;
        border-bottom: 2px solid #E5DDD3;
        margin-bottom: 2rem;
    }
    
    .tab-btn {
        padding: 1rem 0;
        border: none;
        background: none;
        font-size: 1rem;
        font-weight: 600;
        color: #8B7355;
        cursor: pointer;
        position: relative;
        transition: color 0.3s;
    }
    
    .tab-btn::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #2C1810;
        transform: scaleX(0);
        transition: transform 0.3s;
    }
    
    .tab-btn.active {
        color: #2C1810;
    }
    
    .tab-btn.active::after {
        transform: scaleX(1);
    }
    
    .tab-content {
        display: none;
        background: white;
        border-radius: 16px;
        padding: 2rem;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .tab-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: #2C1810;
        margin-bottom: 1rem;
    }
    
    .tab-content p {
        color: #5C4A3D;
        line-height: 1.8;
    }
    
    .specs-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .spec-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .spec-item dt {
        color: #8B7355;
    }
    
    .spec-item dd {
        color: #2C1810;
        font-weight: 500;
    }
    
    /* RELATED PRODUCTS */
    .related-products {
        margin-top: 4rem;
    }
    
    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        color: #2C1810;
        margin-bottom: 2rem;
    }
    
    .related-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
    
    @media (max-width: 1024px) {
        .product-container {
            grid-template-columns: 1fr;
        }
        
        .related-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .product-title {
            font-size: 1.75rem;
        }
        
        .related-grid {
            grid-template-columns: 1fr;
        }
        
        .specs-list {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="product-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-custom">
            <a href="{{ route('frontend.home') }}">Accueil</a>
            <span>/</span>
            <a href="{{ route('frontend.shop') }}">Boutique</a>
            <span>/</span>
            <span class="current">{{ $product->name ?? 'Robe Wax Élégante' }}</span>
        </nav>
        
        <!-- Main Product -->
        <div class="product-container">
            <!-- Gallery -->
            <div class="product-gallery">
                <div class="main-image">
                    <img src="{{ $product->image ?? 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=800&h=1000&fit=crop' }}" alt="{{ $product->name ?? 'Produit' }}" id="mainImage">
                    <button class="zoom-btn"><i class="fas fa-search-plus"></i></button>
                </div>
                <div class="thumbnail-gallery">
                    <div class="thumbnail active">
                        <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=200&h=250&fit=crop" alt="Vue 1">
                    </div>
                    <div class="thumbnail">
                        <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=200&h=250&fit=crop" alt="Vue 2">
                    </div>
                    <div class="thumbnail">
                        <img src="https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=200&h=250&fit=crop" alt="Vue 3">
                    </div>
                    <div class="thumbnail">
                        <img src="https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=200&h=250&fit=crop" alt="Vue 4">
                    </div>
                </div>
            </div>
            
            <!-- Details -->
            <div class="product-details">
                <div class="product-badges">
                    <span class="badge badge-new">Nouveau</span>
                    <span class="badge badge-stock">En stock</span>
                </div>
                
                <div class="product-category">{{ $product->category->name ?? 'Mode Africaine' }}</div>
                <h1 class="product-title">{{ $product->name ?? 'Robe Wax Élégante "Soleil d\'Afrique"' }}</h1>
                
                <div class="product-rating">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="rating-text">4.5/5 (28 avis)</span>
                </div>
                
                <div class="product-price">
                    <span class="current-price">{{ number_format($product->price ?? 129, 2) }} €</span>
                    @if(isset($product->original_price))
                    <span class="original-price">{{ number_format($product->original_price, 2) }} €</span>
                    <span class="discount-badge">-20%</span>
                    @endif
                </div>
                
                <p class="product-description">
                    {{ $product->description ?? 'Cette magnifique robe en wax authentique célèbre l\'élégance africaine. Confectionnée à la main par nos artisans partenaires, elle arbore des motifs traditionnels revisités pour un style contemporain. Coupe ajustée qui sublime la silhouette, tissu de qualité premium 100% coton.' }}
                </p>

                {{-- Section Vendeur --}}
                <div class="mt-6 p-5 rounded-2xl border border-gray-100 bg-white/80 shadow-sm flex gap-4 items-start">
                    @if($product->isBrand())
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold tracking-wide text-gray-900">
                                Vendu par <span class="text-[#ED5F1E]">RACINE BY GANDA</span>
                            </h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Marque officielle — qualité contrôlée par notre atelier
                            </p>
                            <div class="flex gap-2 mt-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Vérifié
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-50 text-yellow-700 text-[11px] font-semibold">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Officiel
                                </span>
                            </div>
                        </div>
                    @else
                        @php
                            $creatorProfile = optional(optional($product->creator)->creatorProfile);
                        @endphp
                        <div class="flex-shrink-0">
                            <img src="{{ $creatorProfile?->logo_path ?? asset('images/default-creator.png') }}"
                                 alt="{{ $creatorProfile?->brand_name }}"
                                 class="w-14 h-14 rounded-full object-cover border-2 border-[#8B5A2B]">
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold tracking-wide text-gray-900">
                                Vendu par 
                                <span class="text-[#8B5A2B]">
                                    {{ $creatorProfile?->brand_name ?? 'Créateur partenaire' }}
                                </span>
                            </h4>
                            @if($creatorProfile?->bio)
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                    {{ $creatorProfile->bio }}
                                </p>
                            @endif

                            <div class="flex flex-wrap items-center gap-3 mt-2 text-[11px] text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Créateur vérifié
                                </span>
                                @if($creatorProfile)
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $product->creator->products()->where('is_active', true)->count() }} produits
                                    </span>
                                @endif
                            </div>

                            @if($creatorProfile && $creatorProfile->slug)
                                <a href="{{ route('frontend.creator.shop', $creatorProfile->slug) }}"
                                   class="inline-flex items-center mt-3 px-4 py-1.5 rounded-full border-2 border-[#8B5A2B] text-[#8B5A2B] text-[11px] font-semibold hover:bg-[#8B5A2B] hover:text-white transition">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    Voir la boutique du créateur
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
                
                <div class="product-options">
                    <!-- Size -->
                    <div class="option-group">
                        <div class="option-label">
                            <span>Taille</span>
                            <a href="#">Guide des tailles</a>
                        </div>
                        <div class="size-options">
                            <button class="size-btn">XS</button>
                            <button class="size-btn">S</button>
                            <button class="size-btn active">M</button>
                            <button class="size-btn">L</button>
                            <button class="size-btn">XL</button>
                        </div>
                    </div>
                    
                    <!-- Quantity -->
                    <div class="option-group">
                        <div class="option-label">
                            <span>Quantité</span>
                        </div>
                        <div class="quantity-selector">
                            <div class="qty-control">
                                <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                                <input type="number" class="qty-input" value="1" min="1" max="{{ $product->stock ?? 1 }}" id="qtyInput" onchange="syncCartQty()">
                                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                            </div>
                            <span style="color: #8B7355; font-size: 0.9rem;">
                                {{ ($product->stock ?? 0) }} disponible{{ ($product->stock ?? 0) > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="product-actions">
                    <form action="{{ route('cart.add') }}" method="POST" id="add-to-cart-form" style="flex: 1; display: flex;">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id ?? 1 }}">
                        <input type="hidden" name="quantity" value="1" id="cartQty">
                        <input type="hidden" name="redirect" value="back">
                        <button type="submit" class="btn-add-cart" id="add-to-cart-btn" style="flex: 1;">
                            <i class="fas fa-shopping-bag"></i>
                            <span id="add-to-cart-text">Ajouter au panier</span>
                        </button>
                    </form>
                    <button class="btn-wishlist" id="wishlistBtn">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                
                <div class="product-extras">
                    <div class="extra-item">
                        <i class="fas fa-truck"></i>
                        <span>Livraison gratuite dès 100€</span>
                    </div>
                    <div class="extra-item">
                        <i class="fas fa-rotate-left"></i>
                        <span>Retours sous 30 jours</span>
                    </div>
                    <div class="extra-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Paiement sécurisé</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="product-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="description">Description</button>
                <button class="tab-btn" data-tab="specs">Caractéristiques</button>
                <button class="tab-btn" data-tab="reviews">Avis (28)</button>
                <button class="tab-btn" data-tab="shipping">Livraison</button>
            </div>
            
            <div class="tab-content active" id="description">
                <h3>Description du produit</h3>
                <p>{{ $product->description ?? 'Cette magnifique robe en wax authentique célèbre l\'élégance africaine. Confectionnée à la main par nos artisans partenaires au Sénégal, elle arbore des motifs traditionnels revisités pour un style contemporain.' }}</p>
                <br>
                <p>La coupe ajustée sublime la silhouette tout en garantissant un confort optimal. Le tissu de qualité premium en 100% coton est doux au toucher et respirant, idéal pour toutes les saisons.</p>
            </div>
            
            <div class="tab-content" id="specs">
                <h3>Caractéristiques</h3>
                <dl class="specs-list">
                    <div class="spec-item">
                        <dt>Matière</dt>
                        <dd>100% Coton Wax</dd>
                    </div>
                    <div class="spec-item">
                        <dt>Origine</dt>
                        <dd>Sénégal</dd>
                    </div>
                    <div class="spec-item">
                        <dt>Coupe</dt>
                        <dd>Ajustée</dd>
                    </div>
                    <div class="spec-item">
                        <dt>Entretien</dt>
                        <dd>Lavage 30°C</dd>
                    </div>
                    <div class="spec-item">
                        <dt>Collection</dt>
                        <dd>Printemps 2025</dd>
                    </div>
                    <div class="spec-item">
                        <dt>Référence</dt>
                        <dd>RAC-{{ $product->id ?? '001' }}-WX</dd>
                    </div>
                </dl>
            </div>
            
            <div class="tab-content" id="reviews">
                <h3>Avis clients</h3>
                <p>Les avis seront bientôt disponibles.</p>
            </div>
            
            <div class="tab-content" id="shipping">
                <h3>Informations de livraison</h3>
                <p><strong>France métropolitaine :</strong> Livraison gratuite dès 100€ d'achat. Sinon 5,90€.</p>
                <br>
                <p><strong>DOM-TOM :</strong> À partir de 12,90€.</p>
                <br>
                <p><strong>International :</strong> Nous contacter pour un devis.</p>
                <br>
                <p>Délai de livraison : 3 à 7 jours ouvrés.</p>
            </div>
        </div>
    </div>
</section>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => 'Boutique', 'url' => route('frontend.shop')],
        ['label' => $product->title ?? 'Produit', 'url' => null],
    ],
    'backUrl' => route('frontend.shop'),
    'backText' => 'Retour à la boutique',
    'position' => 'bottom',
])
@endsection

@push('scripts')
<script>
    // Stock maximum disponible
    const maxStock = {{ $product->stock ?? 0 }};
    
    // Synchroniser quantité input visible avec input hidden
    function syncCartQty() {
        const input = document.getElementById('qtyInput');
        const cartInput = document.getElementById('cartQty');
        let val = parseInt(input.value) || 1;
        
        // Limiter au stock disponible
        if (val > maxStock) {
            val = maxStock;
            input.value = val;
        }
        if (val < 1) {
            val = 1;
            input.value = val;
        }
        
        cartInput.value = val;
    }
    
    // Quantity control
    function changeQty(delta) {
        const input = document.getElementById('qtyInput');
        let val = parseInt(input.value) + delta;
        
        // Limiter entre 1 et stock disponible
        if (val < 1) val = 1;
        if (val > maxStock) val = maxStock;
        
        input.value = val;
        syncCartQty();
    }
    
    // Size selection
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Wishlist toggle
    const wishlistBtn = document.getElementById('wishlistBtn');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            @auth
            const productId = {{ $product->id ?? 0 }};
            fetch('{{ route("profile.wishlist.toggle") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    if (data.is_in_wishlist) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        icon.style.color = '#ED5F1E';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        icon.style.color = '';
                    }
                } else if (data.requires_auth || response.status === 401) {
                    window.location.href = '{{ route("login") }}';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
            @else
            window.location.href = '{{ route("login") }}';
            @endauth
        });
    }
    
    // Fonction utilitaire pour mettre à jour le compteur panier
    function updateCartCount(count) {
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
        } else {
            // Si le badge n'existe pas, le créer
            const cartLink = document.querySelector('a[href="{{ route("cart.index") }}"]');
            if (cartLink && count > 0) {
                let badge = cartLink.querySelector('#cart-count-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.id = 'cart-count-badge';
                    badge.className = 'badge badge-danger position-absolute';
                    badge.style.cssText = 'top: -8px; right: -8px; font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 50%; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;';
                    if (!cartLink.style.position) {
                        cartLink.style.position = 'relative';
                    }
                    cartLink.appendChild(badge);
                }
                badge.textContent = count;
                badge.style.display = 'flex';
            }
        }
        
        // Mettre à jour autres sélecteurs possibles
        document.querySelectorAll('#cart-count, .cart-count').forEach(el => {
            if (el) {
                el.textContent = count;
            }
        });
    }
    
    // Ajout au panier avec AJAX
    const addToCartForm = document.getElementById('add-to-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('add-to-cart-btn');
            const submitText = document.getElementById('add-to-cart-text');
            const originalText = submitText ? submitText.textContent : 'Ajouter au panier';
            
            // Désactiver le bouton
            submitBtn.disabled = true;
            if (submitText) {
                submitText.textContent = 'Ajout...';
            }
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout...';
            
            fetch('{{ route("cart.add") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Ajouté !';
                    submitBtn.style.background = '#22C55E';
                    
                    // Mettre à jour le compteur panier
                    updateCartCount(data.count);
                    
                    // Réinitialiser après 2 secondes
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-shopping-bag"></i> ' + originalText;
                        submitBtn.style.background = '';
                    }, 2000);
                } else {
                    // Erreur
                    alert(data.message || 'Erreur lors de l\'ajout au panier');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-shopping-bag"></i> ' + originalText;
                    
                    // Si stock insuffisant, ajuster la quantité
                    if (data.available_stock) {
                        const qtyInput = document.getElementById('qtyInput');
                        const cartQty = document.getElementById('cartQty');
                        qtyInput.value = data.available_stock;
                        cartQty.value = data.available_stock;
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue. Veuillez réessayer.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-shopping-bag"></i> ' + originalText;
            });
        });
    }
    
    // Thumbnail gallery
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.addEventListener('click', function() {
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('mainImage').src = this.querySelector('img').src.replace('w=200', 'w=800');
        });
    });
    
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
</script>
@endpush
