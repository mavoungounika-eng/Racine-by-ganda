@extends('layouts.frontend')

@section('title', 'Mes Favoris - RACINE BY GANDA')

@push('styles')
<style>
    .wishlist-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .wishlist-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
    }
    
    .wishlist-item-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .wishlist-item-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }
    
    .wishlist-item-image {
        position: relative;
        width: 100%;
        height: 300px;
        overflow: hidden;
        background: #f0f0f0;
    }
    
    .wishlist-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .wishlist-item-card:hover .wishlist-item-image img {
        transform: scale(1.05);
    }
    
    .wishlist-item-actions {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: flex;
        gap: 0.5rem;
    }
    
    .wishlist-remove-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #DC2626;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .wishlist-remove-btn:hover {
        background: #DC2626;
        color: white;
        transform: scale(1.1);
    }
    
    .wishlist-item-info {
        padding: 1.5rem;
    }
    
    .wishlist-item-category {
        font-size: 0.85rem;
        color: #8B7355;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .wishlist-item-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    
    .wishlist-item-title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .wishlist-item-title a:hover {
        color: #ED5F1E;
    }
    
    .wishlist-item-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ED5F1E;
        margin-bottom: 1rem;
    }
    
    .wishlist-item-actions-bottom {
        display: flex;
        gap: 0.75rem;
    }
    
    .btn-add-to-cart {
        flex: 1;
        background: #ED5F1E;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .btn-add-to-cart:hover {
        background: #c44b12;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        color: white;
    }
    
    .empty-wishlist {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .empty-wishlist-icon {
        font-size: 5rem;
        color: #ddd;
        margin-bottom: 1.5rem;
    }
    
    .empty-wishlist-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .empty-wishlist-text {
        color: #8B7355;
        margin-bottom: 2rem;
    }
    
    .btn-clear-all {
        background: rgba(220, 38, 38, 0.1);
        color: #DC2626;
        border: 1px solid #DC2626;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .btn-clear-all:hover {
        background: #DC2626;
        color: white;
    }
    
    @media (max-width: 768px) {
        .wishlist-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .wishlist-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="wishlist-hero">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
                    <i class="fas fa-heart me-3"></i>Mes Favoris
                </h1>
                <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.8); margin: 0;">
                    Retrouvez tous vos produits favoris
                </p>
            </div>
            @if($wishlistItems->count() > 0)
            <form action="{{ route('profile.wishlist.clear') }}" method="POST" class="mt-3 mt-md-0">
                @csrf
                <button type="submit" class="btn-clear-all" onclick="return confirm('Êtes-vous sûr de vouloir supprimer tous vos favoris ?')">
                    <i class="fas fa-trash"></i> Vider la liste
                </button>
            </form>
            @endif
        </div>
    </div>
</section>

<!-- WISHLIST CONTENT -->
<section class="wishlist-content">
    <div class="container">
        @if($wishlistItems->count() > 0)
            <div class="wishlist-grid">
                @foreach($wishlistItems as $item)
                <div class="wishlist-item-card">
                    <div class="wishlist-item-image">
                        <a href="{{ route('frontend.product', $item->product->id) }}">
                            <img src="{{ $item->product->main_image ?? $item->product->image ?? 'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400' }}" 
                                 alt="{{ $item->product->title ?? $item->product->name }}">
                        </a>
                        <div class="wishlist-item-actions">
                            <form action="{{ route('profile.wishlist.remove', $item->product->id) }}" method="POST" class="remove-wishlist-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="wishlist-remove-btn" title="Retirer des favoris">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="wishlist-item-info">
                        <div class="wishlist-item-category">{{ $item->product->category->name ?? 'Mode' }}</div>
                        <h3 class="wishlist-item-title">
                            <a href="{{ route('frontend.product', $item->product->id) }}">
                                {{ $item->product->title ?? $item->product->name ?? 'Produit' }}
                            </a>
                        </h3>
                        <div class="wishlist-item-price">
                            {{ number_format($item->product->price ?? 0, 0, ',', ' ') }} FCFA
                        </div>
                        <div class="wishlist-item-actions-bottom">
                            <a href="{{ route('frontend.product', $item->product->id) }}" class="btn-add-to-cart">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                            @if(($item->product->stock ?? 0) > 0)
                            <form action="{{ route('cart.add') }}" method="POST" style="flex: 1;">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item->product->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect" value="back">
                                <button type="submit" class="btn-add-to-cart" style="width: 100%;">
                                    <i class="fas fa-shopping-bag"></i> Ajouter
                                </button>
                            </form>
                            @else
                            <button type="button" class="btn-add-to-cart" disabled style="opacity: 0.6; cursor: not-allowed; flex: 1;">
                                <i class="fas fa-ban"></i> Stock épuisé
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- PAGINATION -->
            @if($wishlistItems->hasPages())
            <div class="mt-5">
                {{ $wishlistItems->links('pagination::bootstrap-4') }}
            </div>
            @endif
        @else
            <div class="empty-wishlist">
                <div class="empty-wishlist-icon">
                    <i class="far fa-heart"></i>
                </div>
                <h2 class="empty-wishlist-title">Votre liste de favoris est vide</h2>
                <p class="empty-wishlist-text">
                    Commencez à ajouter des produits à vos favoris pour les retrouver facilement plus tard.
                </p>
                <a href="{{ route('frontend.shop') }}" class="btn-add-to-cart" style="display: inline-flex;">
                    <i class="fas fa-store"></i> Découvrir la boutique
                </a>
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
    // AJAX pour retirer des favoris
    document.querySelectorAll('.remove-wishlist-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const productId = formData.get('product_id') || this.action.split('/').pop();
            
            fetch(this.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer la carte du DOM
                    this.closest('.wishlist-item-card').style.transition = 'opacity 0.3s, transform 0.3s';
                    this.closest('.wishlist-item-card').style.opacity = '0';
                    this.closest('.wishlist-item-card').style.transform = 'scale(0.9)';
                    
                    setTimeout(() => {
                        this.closest('.wishlist-item-card').remove();
                        
                        // Si plus de favoris, recharger la page
                        if (document.querySelectorAll('.wishlist-item-card').length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                    
                    // Afficher notification
                    if (typeof showNotification === 'function') {
                        showNotification(data.message, 'success');
                    }
                } else {
                    alert(data.message || 'Erreur lors de la suppression');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de la suppression');
            });
        });
    });
</script>
@endpush

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Favoris', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
@endsection

