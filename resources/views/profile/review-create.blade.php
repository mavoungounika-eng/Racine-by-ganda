@extends('layouts.frontend')

@section('title', 'Laisser un avis - RACINE BY GANDA')

@push('styles')
<style>
    .review-create-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .review-create-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .review-form-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }
    
    .product-review-item {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 2px solid transparent;
        transition: all 0.3s;
    }
    
    .product-review-item:hover {
        border-color: #ED5F1E;
        background: rgba(237, 95, 30, 0.05);
    }
    
    .product-review-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .product-review-image {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        object-fit: cover;
    }
    
    .product-review-info {
        flex: 1;
    }
    
    .product-review-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.25rem;
    }
    
    .product-review-price {
        color: #ED5F1E;
        font-weight: 600;
    }
    
    .rating-input {
        display: flex;
        gap: 0.5rem;
        margin: 1rem 0;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }
    
    .rating-input input[type="radio"] {
        display: none;
    }
    
    .rating-input label {
        font-size: 2rem;
        color: #ddd;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .rating-input label:hover,
    .rating-input label:hover ~ label,
    .rating-input input[type="radio"]:checked ~ label {
        color: #FFB800;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .form-control {
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        padding: 0.75rem 1rem;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #ED5F1E;
        box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.1);
    }
    
    .btn-submit-review {
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }
    
    .btn-submit-review:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(237, 95, 30, 0.4);
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="review-create-hero">
    <div class="container">
        <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
            <i class="fas fa-star me-3"></i>Laisser un avis
        </h1>
        <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.8); margin: 0;">
            Commande #{{ $order->id }} - Partagez votre expérience
        </p>
    </div>
</section>

<!-- REVIEW CREATE CONTENT -->
<section class="review-create-content">
    <div class="container">
        @if($reviewableProducts->count() > 0)
            @foreach($reviewableProducts as $item)
            <div class="review-form-card">
                <div class="product-review-item">
                    <div class="product-review-header">
                        <img src="{{ $item['product']->main_image ?? $item['product']->image ?? 'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400' }}" 
                             alt="{{ $item['product']->title ?? 'Produit' }}"
                             class="product-review-image">
                        <div class="product-review-info">
                            <h3 class="product-review-title">{{ $item['product']->title ?? $item['product']->name ?? 'Produit' }}</h3>
                            <div class="product-review-price">
                                {{ number_format($item['product']->price ?? 0, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                    
                    <form action="{{ route('profile.reviews.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        
                        <div class="form-group">
                            <label class="form-label">Note *</label>
                            <div class="rating-input">
                                <input type="radio" id="rating_{{ $item['product']->id }}_5" name="rating" value="5" required>
                                <label for="rating_{{ $item['product']->id }}_5">★</label>
                                <input type="radio" id="rating_{{ $item['product']->id }}_4" name="rating" value="4">
                                <label for="rating_{{ $item['product']->id }}_4">★</label>
                                <input type="radio" id="rating_{{ $item['product']->id }}_3" name="rating" value="3">
                                <label for="rating_{{ $item['product']->id }}_3">★</label>
                                <input type="radio" id="rating_{{ $item['product']->id }}_2" name="rating" value="2">
                                <label for="rating_{{ $item['product']->id }}_2">★</label>
                                <input type="radio" id="rating_{{ $item['product']->id }}_1" name="rating" value="1">
                                <label for="rating_{{ $item['product']->id }}_1">★</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment_{{ $item['product']->id }}" class="form-label">Commentaire (optionnel)</label>
                            <textarea name="comment" id="comment_{{ $item['product']->id }}" 
                                      class="form-control" 
                                      rows="4" 
                                      placeholder="Partagez votre expérience avec ce produit..."
                                      maxlength="1000"></textarea>
                            <small class="text-muted">Maximum 1000 caractères</small>
                        </div>
                        
                        <button type="submit" class="btn-submit-review">
                            <i class="fas fa-paper-plane me-2"></i> Publier mon avis
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        @else
            <div class="review-form-card text-center">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #22C55E; margin-bottom: 1rem;"></i>
                <h3 style="color: #2C1810; margin-bottom: 0.5rem;">Tous les produits ont déjà été notés</h3>
                <p style="color: #8B7355; margin-bottom: 2rem;">
                    Vous avez déjà laissé un avis pour tous les produits de cette commande.
                </p>
                <a href="{{ route('profile.orders.show', $order) }}" class="btn-submit-review" style="display: inline-flex; align-items: center; text-decoration: none;">
                    <i class="fas fa-arrow-left me-2"></i> Retour à la commande
                </a>
            </div>
        @endif
    </div>
</section>

@php
    $order = $order ?? null;
    $backUrl = $order ? route('profile.orders.show', $order) : route('profile.orders');
@endphp

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Commandes', 'url' => route('profile.orders')],
        ['label' => 'Laisser un avis', 'url' => null],
    ],
    'backUrl' => $backUrl,
    'backText' => 'Retour à la commande',
    'position' => 'bottom',
])
@endsection

