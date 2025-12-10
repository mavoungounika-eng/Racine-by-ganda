@extends('layouts.frontend')

@section('title', 'Mes Avis - RACINE BY GANDA')

@push('styles')
<style>
    .reviews-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .reviews-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .review-item-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .review-item-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .review-product {
        flex: 1;
    }
    
    .review-product-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .review-product-title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .review-product-title a:hover {
        color: #ED5F1E;
    }
    
    .review-rating {
        display: flex;
        gap: 0.25rem;
        margin-bottom: 0.75rem;
    }
    
    .star {
        color: #FFB800;
        font-size: 1.25rem;
    }
    
    .star.empty {
        color: #ddd;
    }
    
    .review-comment {
        color: #6c757d;
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .review-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .review-date {
        font-size: 0.9rem;
        color: #8B7355;
    }
    
    .review-badge {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .badge-verified {
        background: rgba(34, 197, 94, 0.1);
        color: #22C55E;
    }
    
    .badge-pending {
        background: rgba(255, 184, 0, 0.1);
        color: #FFB800;
    }
    
    .review-actions {
        display: flex;
        gap: 0.75rem;
    }
    
    .btn-edit-review {
        background: rgba(237, 95, 30, 0.1);
        color: #ED5F1E;
        border: 1px solid #ED5F1E;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-edit-review:hover {
        background: #ED5F1E;
        color: white;
    }
    
    .btn-delete-review {
        background: rgba(220, 38, 38, 0.1);
        color: #DC2626;
        border: 1px solid #DC2626;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-delete-review:hover {
        background: #DC2626;
        color: white;
    }
    
    .empty-reviews {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .empty-reviews-icon {
        font-size: 5rem;
        color: #ddd;
        margin-bottom: 1.5rem;
    }
    
    .empty-reviews-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .empty-reviews-text {
        color: #8B7355;
        margin-bottom: 2rem;
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="reviews-hero">
    <div class="container">
        <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
            <i class="fas fa-star me-3"></i>Mes Avis
        </h1>
        <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.8); margin: 0;">
            Gérez tous vos avis et commentaires sur les produits
        </p>
    </div>
</section>

<!-- REVIEWS CONTENT -->
<section class="reviews-content">
    <div class="container">
        @if($reviews->count() > 0)
            @foreach($reviews as $review)
            <div class="review-item-card">
                <div class="review-header">
                    <div class="review-product">
                        <h3 class="review-product-title">
                            <a href="{{ route('frontend.product', $review->product->id) }}">
                                {{ $review->product->title ?? $review->product->name ?? 'Produit' }}
                            </a>
                        </h3>
                        <div class="review-rating">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star star {{ $i <= $review->rating ? '' : 'empty' }}"></i>
                            @endfor
                            <span style="margin-left: 0.5rem; color: #8B7355; font-size: 0.9rem;">({{ $review->rating }}/5)</span>
                        </div>
                        @if($review->comment)
                        <p class="review-comment">{{ $review->comment }}</p>
                        @endif
                    </div>
                </div>
                <div class="review-meta">
                    <div>
                        <span class="review-date">
                            <i class="far fa-clock me-1"></i>
                            Publié le {{ $review->created_at->format('d/m/Y') }}
                        </span>
                        @if($review->is_verified_purchase)
                        <span class="review-badge badge-verified ms-3">
                            <i class="fas fa-check-circle me-1"></i>Achat vérifié
                        </span>
                        @endif
                        @if(!$review->is_approved)
                        <span class="review-badge badge-pending ms-3">
                            <i class="fas fa-clock me-1"></i>En attente de validation
                        </span>
                        @endif
                    </div>
                    <div class="review-actions">
                        <a href="{{ route('profile.reviews.edit', $review) }}" class="btn-edit-review">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        <form action="{{ route('profile.reviews.destroy', $review) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete-review">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
            
            <!-- PAGINATION -->
            @if($reviews->hasPages())
            <div class="mt-5">
                {{ $reviews->links('pagination::bootstrap-4') }}
            </div>
            @endif
        @else
            <div class="empty-reviews">
                <div class="empty-reviews-icon">
                    <i class="far fa-star"></i>
                </div>
                <h2 class="empty-reviews-title">Vous n'avez pas encore laissé d'avis</h2>
                <p class="empty-reviews-text">
                    Partagez votre expérience en laissant un avis sur les produits que vous avez achetés.
                </p>
                <a href="{{ route('profile.orders') }}" class="btn-edit-review" style="display: inline-flex; align-items: center;">
                    <i class="fas fa-shopping-bag me-2"></i>Voir mes commandes
                </a>
            </div>
        @endif
    </div>
</section>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Avis', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
@endsection

