@extends('layouts.frontend')

@section('title', 'Mon Panier - RACINE BY GANDA')

@push('styles')
<style>
    .cart-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 4rem 0;
        margin-top: -70px;
        padding-top: calc(4rem + 70px);
    }
    
    .cart-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .breadcrumb-custom {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }
    
    .breadcrumb-custom a {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
    }
    
    .breadcrumb-custom a:hover {
        color: #D4A574;
    }
    
    .breadcrumb-custom span {
        color: rgba(255, 255, 255, 0.4);
    }
    
    .breadcrumb-custom .current {
        color: #D4A574;
    }
    
    .cart-section {
        padding: 3rem 0 5rem;
        background: #F8F6F3;
        min-height: 50vh;
    }
    
    .cart-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
        align-items: start;
    }
    
    /* CART ITEMS */
    .cart-items {
        background: white;
        border-radius: 24px;
        overflow: hidden;
    }
    
    .cart-header {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 50px;
        gap: 1rem;
        padding: 1.25rem 2rem;
        background: #F8F6F3;
        font-weight: 600;
        color: #8B7355;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .cart-item {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 50px;
        gap: 1rem;
        padding: 1.5rem 2rem;
        align-items: center;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .item-product {
        display: flex;
        gap: 1.25rem;
        align-items: center;
    }
    
    .item-image {
        width: 100px;
        height: 120px;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .item-details h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.35rem;
    }
    
    .item-details .ref {
        color: #8B7355;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
    
    .item-details .variant {
        display: inline-block;
        background: #F8F6F3;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #5C4A3D;
    }
    
    .item-price {
        font-weight: 600;
        color: #2C1810;
        font-size: 1.05rem;
    }
    
    .item-quantity {
        display: flex;
        align-items: center;
        border: 1.5px solid #E5DDD3;
        border-radius: 10px;
        overflow: hidden;
        width: fit-content;
    }
    
    .qty-btn {
        width: 38px;
        height: 38px;
        border: none;
        background: #F8F6F3;
        color: #5C4A3D;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .qty-btn:hover {
        background: #E5DDD3;
    }
    
    .qty-input {
        width: 45px;
        height: 38px;
        border: none;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
        color: #2C1810;
    }
    
    .qty-input:focus {
        outline: none;
    }
    
    .item-total {
        font-weight: 700;
        color: #8B5A2B;
        font-size: 1.1rem;
    }
    
    .item-remove {
        width: 40px;
        height: 40px;
        border: none;
        background: transparent;
        color: #aaa;
        font-size: 1.1rem;
        cursor: pointer;
        border-radius: 50%;
        transition: all 0.3s;
    }
    
    .item-remove:hover {
        background: #FEF2F2;
        color: #EF4444;
    }
    
    /* CART SUMMARY */
    .cart-summary {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        position: sticky;
        top: 100px;
    }
    
    .summary-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        color: #5C4A3D;
    }
    
    .summary-row.total {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 2px solid #E5DDD3;
        font-size: 1.25rem;
        font-weight: 700;
        color: #2C1810;
    }
    
    .summary-row.total span:last-child {
        color: #8B5A2B;
    }
    
    .free-shipping {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        color: #16A34A;
        font-size: 0.95rem;
    }
    
    .free-shipping i {
        font-size: 1.25rem;
    }
    
    .promo-code {
        margin-bottom: 1.5rem;
    }
    
    .promo-code label {
        display: block;
        font-weight: 500;
        color: #2C1810;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .promo-input {
        display: flex;
        gap: 0.5rem;
    }
    
    .promo-input input {
        flex: 1;
        padding: 0.75rem 1rem;
        border: 1.5px solid #E5DDD3;
        border-radius: 10px;
        font-size: 0.95rem;
    }
    
    .promo-input input:focus {
        outline: none;
        border-color: #D4A574;
    }
    
    .promo-input button {
        padding: 0.75rem 1.25rem;
        background: #F8F6F3;
        border: 1.5px solid #E5DDD3;
        border-radius: 10px;
        font-weight: 600;
        color: #5C4A3D;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .promo-input button:hover {
        background: #E5DDD3;
    }
    
    .btn-checkout {
        width: 100%;
        padding: 1.1rem;
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
        text-decoration: none;
    }
    
    .btn-checkout:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 40px rgba(44, 24, 16, 0.25);
        color: white;
    }
    
    .btn-continue {
        width: 100%;
        padding: 0.9rem;
        background: transparent;
        color: #8B7355;
        border: none;
        font-size: 0.95rem;
        cursor: pointer;
        margin-top: 1rem;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: color 0.3s;
    }
    
    .btn-continue:hover {
        color: #8B5A2B;
    }
    
    .secure-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #E5DDD3;
        color: #8B7355;
        font-size: 0.85rem;
    }
    
    .secure-badge i {
        color: #22C55E;
    }
    
    /* EMPTY CART */
    .empty-cart {
        text-align: center;
        padding: 5rem 2rem;
        background: white;
        border-radius: 24px;
    }
    
    .empty-cart-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(139, 90, 43, 0.1) 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        font-size: 3rem;
        color: #D4A574;
    }
    
    .empty-cart h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .empty-cart p {
        color: #8B7355;
        font-size: 1.05rem;
        margin-bottom: 2rem;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .btn-shop {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-shop:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(212, 165, 116, 0.3);
        color: white;
    }
    
    /* FEATURES */
    .cart-features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-top: 3rem;
    }
    
    .feature-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .feature-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(139, 90, 43, 0.1) 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: #D4A574;
        flex-shrink: 0;
    }
    
    .feature-text h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.25rem;
    }
    
    .feature-text span {
        font-size: 0.85rem;
        color: #8B7355;
    }
    
    @media (max-width: 1024px) {
        .cart-grid {
            grid-template-columns: 1fr;
        }
        
        .cart-summary {
            position: static;
        }
        
        .cart-header {
            display: none;
        }
        
        .cart-item {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .item-product {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .item-image {
            width: 100%;
            height: 200px;
        }
        
        .cart-features {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .cart-hero h1 {
            font-size: 2.5rem;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="cart-hero">
    <div class="container">
        <nav class="breadcrumb-custom mb-3">
            <a href="{{ route('frontend.home') }}">Accueil</a>
            <span>/</span>
            <span class="current">Panier</span>
        </nav>
        <h1>Mon Panier</h1>
    </div>
</section>

<!-- CART -->
<section class="cart-section">
    <div class="container">
        @if($items && $items->count() > 0)
        @php 
            $itemCount = $items->sum(function($item) {
                return is_object($item) && isset($item->quantity) ? $item->quantity : (is_array($item) ? $item['quantity'] : 0);
            });
            $freeShipping = $total >= 100000; // 100 000 FCFA pour livraison gratuite
        @endphp
        
        <div class="cart-grid">
            <!-- ITEMS -->
            <div class="cart-items">
                <div class="cart-header">
                    <span>Produit</span>
                    <span>Prix</span>
                    <span>Quantité</span>
                    <span>Total</span>
                    <span></span>
                </div>
                
                @foreach($items as $item)
                @php 
                    // Gérer à la fois CartItem (Database) et array (Session)
                    $productId = is_object($item) ? $item->product_id : $item['product_id'];
                    $quantity = is_object($item) ? $item->quantity : $item['quantity'];
                    $price = is_object($item) ? $item->price : $item['price'];
                    $product = is_object($item) && $item->relationLoaded('product') ? $item->product : null;
                    $title = $product ? $product->title : (is_array($item) ? ($item['title'] ?? $item['name'] ?? 'Produit') : 'Produit');
                    $mainImage = $product ? $product->main_image : (is_array($item) ? ($item['main_image'] ?? null) : null);
                    $subtotal = $price * $quantity;
                @endphp
                <div class="cart-item">
                    <div class="item-product">
                        <div class="item-image">
                            <img src="{{ $mainImage ? asset('storage/' . $mainImage) : 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=200&h=250&fit=crop' }}" alt="{{ $title }}">
                        </div>
                        <div class="item-details">
                            <h3>{{ $title }}</h3>
                            <p class="ref">Réf: RAC-{{ $productId }}</p>
                            @if(is_array($item) && isset($item['size']))
                            <span class="variant">Taille: {{ $item['size'] }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="item-price">{{ number_format($price, 0, ',', ' ') }} FCFA</div>
                    
                    <div class="item-quantity">
                        <form action="{{ route('cart.update') }}" method="POST" class="d-flex align-items-center">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $productId }}">
                            <button type="button" class="qty-btn" onclick="updateQty(this, -1)">−</button>
                            <input type="number" name="quantity" class="qty-input" value="{{ $quantity }}" min="1" onchange="this.form.submit()">
                            <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                        </form>
                    </div>
                    
                    <div class="item-total">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</div>
                    
                    <form action="{{ route('cart.remove') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $productId }}">
                        <button type="submit" class="item-remove" title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            
            <!-- SUMMARY -->
            <div class="cart-summary">
                <h2 class="summary-title">Récapitulatif</h2>
                
                @if($freeShipping)
                <div class="free-shipping">
                    <i class="fas fa-check-circle"></i>
                    <span>Félicitations ! Vous bénéficiez de la livraison gratuite</span>
                </div>
                @else
                <div class="free-shipping" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.2); color: #D97706;">
                    <i class="fas fa-truck"></i>
                    <span>Plus que {{ number_format(100000 - $total, 0, ',', ' ') }} FCFA pour la livraison gratuite</span>
                </div>
                @endif
                
                <div class="summary-row">
                    <span>Sous-total ({{ $itemCount }} article{{ $itemCount > 1 ? 's' : '' }})</span>
                    <span>{{ number_format($total, 0, ',', ' ') }} FCFA</span>
                </div>
                
                <div class="summary-row">
                    <span>Livraison</span>
                    <span>{{ $freeShipping ? 'Gratuite' : '5 900 FCFA' }}</span>
                </div>
                
                <div class="promo-code">
                    <label>Code promo</label>
                    <div class="promo-input">
                        <input type="text" placeholder="Entrez votre code">
                        <button>Appliquer</button>
                    </div>
                </div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span>{{ number_format($total + ($freeShipping ? 0 : 5900), 0, ',', ' ') }} FCFA</span>
                </div>
                
                <a href="{{ route('checkout.index') }}" class="btn-checkout">
                    <i class="fas fa-lock"></i>
                    Passer la commande
                </a>
                
                <a href="{{ route('frontend.shop') }}" class="btn-continue">
                    <i class="fas fa-arrow-left"></i> Continuer mes achats
                </a>
                
                <div class="secure-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Paiement 100% sécurisé</span>
                </div>
            </div>
        </div>
        
        <!-- FEATURES -->
        <div class="cart-features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-truck"></i></div>
                <div class="feature-text">
                    <h4>Livraison gratuite</h4>
                    <span>Dès 100€ d'achat</span>
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-rotate-left"></i></div>
                <div class="feature-text">
                    <h4>Retours faciles</h4>
                    <span>30 jours pour changer d'avis</span>
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <div class="feature-text">
                    <h4>Service client</h4>
                    <span>À votre écoute 7j/7</span>
                </div>
            </div>
        </div>
        
        @else
        <!-- EMPTY CART -->
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h2>Votre panier est vide</h2>
            <p>Vous n'avez pas encore ajouté d'articles à votre panier. Découvrez nos créations uniques !</p>
            <a href="{{ route('frontend.shop') }}" class="btn-shop">
                <i class="fas fa-store"></i>
                Découvrir la boutique
            </a>
        </div>
        
        <!-- FEATURES -->
        <div class="cart-features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-truck"></i></div>
                <div class="feature-text">
                    <h4>Livraison gratuite</h4>
                    <span>Dès 100€ d'achat</span>
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-rotate-left"></i></div>
                <div class="feature-text">
                    <h4>Retours faciles</h4>
                    <span>30 jours pour changer d'avis</span>
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <div class="feature-text">
                    <h4>Service client</h4>
                    <span>À votre écoute 7j/7</span>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => 'Boutique', 'url' => route('frontend.shop')],
        ['label' => 'Panier', 'url' => null],
    ],
    'backUrl' => route('frontend.shop'),
    'backText' => 'Continuer mes achats',
    'position' => 'bottom',
])
@endsection

@push('scripts')
<script>
function updateQty(btn, delta) {
    const form = btn.closest('form');
    const input = form.querySelector('.qty-input');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    input.value = val;
    form.submit();
}
</script>
@endpush
