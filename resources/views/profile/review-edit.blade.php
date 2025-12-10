@extends('layouts.frontend')

@section('title', 'Modifier mon avis - RACINE BY GANDA')

@push('styles')
<style>
    .review-edit-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .review-edit-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .review-edit-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        max-width: 800px;
        margin: 0 auto;
    }
    
    .product-info {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .product-image {
        width: 100px;
        height: 100px;
        border-radius: 12px;
        object-fit: cover;
    }
    
    .product-details {
        flex: 1;
    }
    
    .product-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .product-price {
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
<section class="review-edit-hero">
    <div class="container">
        <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
            <i class="fas fa-edit me-3"></i>Modifier mon avis
        </h1>
        <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.8); margin: 0;">
            Mettez à jour votre avis sur ce produit
        </p>
    </div>
</section>

<!-- REVIEW EDIT CONTENT -->
<section class="review-edit-content">
    <div class="container">
        <div class="review-edit-card">
            <div class="product-info">
                <img src="{{ $review->product->main_image ?? $review->product->image ?? 'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400' }}" 
                     alt="{{ $review->product->title ?? 'Produit' }}"
                     class="product-image">
                <div class="product-details">
                    <h3 class="product-title">{{ $review->product->title ?? $review->product->name ?? 'Produit' }}</h3>
                    <div class="product-price">
                        {{ number_format($review->product->price ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
            
            <form action="{{ route('profile.reviews.update', $review) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label class="form-label">Note *</label>
                    <div class="rating-input">
                        <input type="radio" id="rating_5" name="rating" value="5" {{ $review->rating == 5 ? 'checked' : '' }} required>
                        <label for="rating_5">★</label>
                        <input type="radio" id="rating_4" name="rating" value="4" {{ $review->rating == 4 ? 'checked' : '' }}>
                        <label for="rating_4">★</label>
                        <input type="radio" id="rating_3" name="rating" value="3" {{ $review->rating == 3 ? 'checked' : '' }}>
                        <label for="rating_3">★</label>
                        <input type="radio" id="rating_2" name="rating" value="2" {{ $review->rating == 2 ? 'checked' : '' }}>
                        <label for="rating_2">★</label>
                        <input type="radio" id="rating_1" name="rating" value="1" {{ $review->rating == 1 ? 'checked' : '' }}>
                        <label for="rating_1">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comment" class="form-label">Commentaire (optionnel)</label>
                    <textarea name="comment" id="comment" 
                              class="form-control" 
                              rows="4" 
                              placeholder="Partagez votre expérience avec ce produit..."
                              maxlength="1000">{{ old('comment', $review->comment) }}</textarea>
                    <small class="text-muted">Maximum 1000 caractères</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-submit-review">
                        <i class="fas fa-save me-2"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('profile.reviews') }}" class="btn-submit-review" style="background: rgba(108, 117, 125, 0.1); color: #6c757d; text-decoration: none; display: flex; align-items: center;">
                        <i class="fas fa-times me-2"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Avis', 'url' => route('profile.reviews')],
        ['label' => 'Modifier avis', 'url' => null],
    ],
    'backUrl' => route('profile.reviews'),
    'backText' => 'Retour à mes avis',
    'position' => 'bottom',
])
@endsection

