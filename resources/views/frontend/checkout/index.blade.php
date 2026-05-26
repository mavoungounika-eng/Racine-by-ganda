@extends('layouts.frontend')

@section('content')
@php
    $appliedPromoCode     = session('applied_promo_code_code');
    $appliedPromoDiscount = (int) session('applied_promo_discount', 0);
    $promoFreeShipping    = session('applied_promo_free_shipping', false);
    $effectiveShipping    = ($promoFreeShipping) ? 0 : $shipping_default;
    $effectiveTotal       = max(0, $subtotal - $appliedPromoDiscount + $effectiveShipping);
@endphp
<div class="container py-5">
    {{-- Messages flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erreur de validation :</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- En-tête avec stepper --}}
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-4" style="color: #160D0C; font-weight: 700; letter-spacing: 0.05em;">Finaliser ma commande</h1>
            
            {{-- Stepper visuel --}}
            <div class="checkout-stepper mb-4">
                <div class="stepper-item completed">
                    <div class="stepper-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stepper-label">Panier</div>
                </div>
                <div class="stepper-line"></div>
                <div class="stepper-item active">
                    <div class="stepper-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stepper-label">Informations</div>
                </div>
                <div class="stepper-line"></div>
                <div class="stepper-item">
                    <div class="stepper-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stepper-label">Paiement</div>
                </div>
                <div class="stepper-line"></div>
                <div class="stepper-item">
                    <div class="stepper-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stepper-label">Confirmation</div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('checkout.place') }}" method="POST">
        @csrf
        {{-- ✅ Module 8 - Protection double soumission : Token unique --}}
        @if(isset($checkoutToken))
            <input type="hidden" name="_checkout_token" value="{{ $checkoutToken }}">
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        @endif

        <div class="row">
            {{-- Colonne gauche : Informations client et adresse --}}
            <div class="col-lg-8 mb-4">
                {{-- Informations de contact --}}
                <div class="card mb-4">
                    <div class="card-header font-weight-bold bg-dark text-white">
                        Informations de contact
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="full_name">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('full_name') is-invalid @enderror" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="{{ old('full_name', $user->name ?? '') }}" 
                                   required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email ?? '') }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone ?? '') }}" 
                                   required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Adresse de livraison --}}
                <div class="card mb-4">
                    <div class="card-header font-weight-bold bg-dark text-white">
                        Adresse de livraison
                    </div>
                    <div class="card-body">

                        {{-- Sélecteur d'adresses sauvegardées --}}
                        @if($addresses->count() > 0)
                        <div class="mb-4" id="saved-addresses-section">
                            <p class="text-muted mb-2" style="font-size:0.9rem;">Choisir une adresse sauvegardée :</p>
                            <div class="row g-2" id="address-cards">
                                @foreach($addresses as $addr)
                                <div class="col-12 col-md-6">
                                    <div class="address-card {{ ($defaultAddress && $defaultAddress->id === $addr->id) ? 'selected' : '' }}"
                                         data-address-id="{{ $addr->id }}"
                                         data-line1="{{ $addr->address_line_1 }}"
                                         data-line2="{{ $addr->address_line_2 ?? '' }}"
                                         data-city="{{ $addr->city }}"
                                         data-postal="{{ $addr->postal_code ?? '' }}"
                                         data-country="{{ $addr->country }}"
                                         onclick="selectAddress(this)">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong style="font-size:0.85rem;">{{ $addr->first_name }} {{ $addr->last_name }}</strong>
                                                @if($addr->is_default) <span class="badge bg-secondary ms-1" style="font-size:0.7rem;">Par défaut</span> @endif
                                            </div>
                                            <i class="fas fa-check-circle address-check" style="color:#ED5F1E;display:none;"></i>
                                        </div>
                                        <small class="text-muted d-block mt-1">{{ $addr->address_line_1 }}@if($addr->address_line_2), {{ $addr->address_line_2 }}@endif</small>
                                        <small class="text-muted">{{ $addr->city }}@if($addr->postal_code) {{ $addr->postal_code }}@endif, {{ $addr->country }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-link ps-0 mt-2" onclick="toggleManualAddress()" id="toggle-manual-btn">
                                <i class="fas fa-plus me-1"></i>Utiliser une autre adresse
                            </button>
                        </div>
                        @endif

                        <div id="manual-address-form" @if($addresses->count() > 0) style="display:none;" @endif>
                        <div class="form-group">
                            <label for="address_line1">Adresse <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('address_line1') is-invalid @enderror"
                                   id="address_line1"
                                   name="address_line1"
                                   value="{{ old('address_line1', $defaultAddress->address_line_1 ?? '') }}"
                                   placeholder="Rue, numéro, quartier"
                                   required>
                            @error('address_line1')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="address_line2">Complément d'adresse</label>
                            <input type="text"
                                   class="form-control @error('address_line2') is-invalid @enderror"
                                   id="address_line2"
                                   name="address_line2"
                                   value="{{ old('address_line2', $defaultAddress->address_line_2 ?? '') }}"
                                   placeholder="Appartement, bâtiment, étage (optionnel)">
                            @error('address_line2')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="city">Ville <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('city') is-invalid @enderror"
                                           id="city"
                                           name="city"
                                           value="{{ old('city', $defaultAddress->city ?? '') }}"
                                           required>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="postal_code">Code postal</label>
                                    <input type="text"
                                           class="form-control @error('postal_code') is-invalid @enderror"
                                           id="postal_code"
                                           name="postal_code"
                                           value="{{ old('postal_code', $defaultAddress->postal_code ?? '') }}"
                                           placeholder="BP / Code postal">
                                    @error('postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="country">Pays <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('country') is-invalid @enderror"
                                           id="country"
                                           name="country"
                                           value="{{ old('country', 'Congo') }}"
                                           required>
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        </div>{{-- #manual-address-form --}}
                    </div>
                </div>

                {{-- Mode de livraison --}}
                <div class="card mb-4">
                    <div class="card-header font-weight-bold bg-dark text-white">
                        Livraison
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="shipping_method" 
                                   id="shipping_home" 
                                   value="home_delivery" 
                                   {{ old('shipping_method', 'home_delivery') === 'home_delivery' ? 'checked' : '' }}
                                   required>
                            <label class="form-check-label" for="shipping_home">
                                <strong>Livraison à domicile</strong> – {{ format_price(2000) }}
                            </label>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="shipping_method" 
                                   id="shipping_pickup" 
                                   value="showroom_pickup" 
                                   {{ old('shipping_method') === 'showroom_pickup' ? 'checked' : '' }}
                                   required>
                            <label class="form-check-label" for="shipping_pickup">
                                <strong>Retrait au showroom</strong> – Gratuit
                            </label>
                        </div>
                        @error('shipping_method')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Mode de paiement --}}
                <div class="card mb-4">
                    <div class="card-header font-weight-bold bg-dark text-white">
                        Mode de paiement
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="payment_method" 
                                   id="pay_mm" 
                                   value="mobile_money" 
                                   {{ old('payment_method', 'mobile_money') === 'mobile_money' ? 'checked' : '' }}
                                   required>
                            <label class="form-check-label" for="pay_mm">
                                <strong>Mobile Money</strong> (MTN / Airtel)
                            </label>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="payment_method" 
                                   id="pay_card" 
                                   value="card" 
                                   {{ old('payment_method') === 'card' ? 'checked' : '' }}
                                   required>
                            <label class="form-check-label" for="pay_card">
                                <strong>Carte bancaire</strong>
                            </label>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="payment_method" 
                                   id="pay_cod" 
                                   value="cash_on_delivery" 
                                   {{ old('payment_method') === 'cash_on_delivery' ? 'checked' : '' }}
                                   required>
                            <label class="form-check-label" for="pay_cod">
                                <strong>Paiement à la livraison</strong>
                            </label>
                        </div>
                        @error('payment_method')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Colonne droite : Résumé de la commande --}}
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header font-weight-bold bg-dark text-white">
                        Résumé de la commande
                    </div>
                    <div class="card-body">
                        <h3 class="sr-only">Articles dans votre panier</h3>
                        <ul class="list-unstyled mb-3">
                            @foreach($items as $item)
                                @php
                                    $product = Auth::check() ? $item->product : (object)$item;
                                    $qty = Auth::check() ? $item->quantity : $item['quantity'];
                                    $price = Auth::check() ? $item->price : $item['price'];
                                    $image = ($product && Auth::check()) ? ($product->main_image ?? null) : ($item['main_image'] ?? null);
                                    $title = ($product && Auth::check()) ? ($product->title ?? 'Produit supprimé') : ($item['title'] ?? 'Produit');
                                @endphp
                                @if(!$product && Auth::check())
                                    @continue
                                @endif
                                <li class="d-flex mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        @if($image)
                                            <img src="{{ asset('storage/products/' . $image) }}" 
                                                 alt="{{ $title }}" 
                                                 class="img-thumbnail" 
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">{{ $title }}</h6>
                                        <small class="text-muted">Qté : {{ $qty }}</small>
                                        <div class="mt-1">
                                            <strong>{{ format_price($price * $qty) }}</strong>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total</span>
                                <strong>{{ format_price($subtotal) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Livraison</span>
                                <strong id="shipping-cost-display">{{ format_price($effectiveShipping) }}</strong>
                            </div>
                            @if($appliedPromoCode)
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span><i class="fas fa-tag me-1"></i>{{ $appliedPromoCode }}</span>
                                <strong>
                                    @if($promoFreeShipping)
                                        Livraison offerte
                                    @else
                                        −{{ number_format($appliedPromoDiscount, 0, ',', ' ') }} FCFA
                                    @endif
                                </strong>
                            </div>
                            @endif
                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between">
                                    <strong>Total</strong>
                                    <strong class="text-primary" id="total-display" data-subtotal="{{ $subtotal }}">{{ format_price($effectiveTotal) }}</strong>
                                </div>
                            </div>

                            {{-- Section code promo --}}
                            <div class="mt-3 pt-3 border-top">
                                @if($appliedPromoCode)
                                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.25);">
                                    <small class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Code {{ $appliedPromoCode }} appliqué</small>
                                    <button type="button" id="checkout-remove-promo" class="btn btn-sm btn-link text-danger p-0 ms-2" style="font-size:0.8rem;">Retirer</button>
                                </div>
                                @else
                                <div class="input-group input-group-sm">
                                    <input type="text" id="checkout-promo-input" class="form-control" placeholder="Code promo">
                                    <button class="btn btn-outline-secondary" type="button" id="checkout-apply-promo">Appliquer</button>
                                </div>
                                <div id="checkout-promo-feedback" style="display:none;font-size:0.8rem;" class="mt-1"></div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg btn-block checkout-submit-btn">
                                <i class="fas fa-lock me-2"></i>
                                Valider ma commande
                            </button>
                            <p class="text-center text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-shield-alt me-1"></i>
                                Paiement 100% sécurisé
                            </p>
                        </div>

                        {{-- Support / Contact --}}
                        <div class="mt-4 pt-4 border-top">
                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Besoin d'aide ?</small>
                                <a href="{{ route('frontend.contact') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-envelope me-1"></i>
                                    Nous contacter
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style nonce="{{ csp_nonce() }}">
    /* Stepper visuel */
    .checkout-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 600px;
        margin: 0 auto;
        padding: 1.5rem;
        background: rgba(22,13,12,0.05);
        border-radius: 12px;
    }

    .stepper-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        position: relative;
    }

    .stepper-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(22,13,12,0.1);
        color: rgba(22,13,12,0.5);
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
        transition: all 0.3s;
    }

    .stepper-item.active .stepper-icon {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }

    .stepper-item.completed .stepper-icon {
        background: #22C55E;
        color: white;
    }

    .stepper-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(22,13,12,0.5);
        text-align: center;
    }

    .stepper-item.active .stepper-label {
        color: #ED5F1E;
    }

    .stepper-item.completed .stepper-label {
        color: #22C55E;
    }

    .stepper-line {
        flex: 1;
        height: 2px;
        background: rgba(22,13,12,0.1);
        margin: 0 1rem;
        position: relative;
        top: -25px;
    }

    .stepper-item.completed + .stepper-line {
        background: #22C55E;
    }

    /* Amélioration des cards */
    .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        overflow: hidden;
    }

    .card-header {
        border-bottom: 2px solid rgba(237, 95, 30, 0.2);
    }

    /* Bouton submit amélioré */
    .checkout-submit-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%) !important;
        border: none !important;
        padding: 1rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.05em !important;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3) !important;
        transition: all 0.3s !important;
    }

    .checkout-submit-btn:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px rgba(237, 95, 30, 0.4) !important;
    }

    @media (max-width: 768px) {
        .checkout-stepper {
            padding: 1rem;
        }

        .stepper-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .stepper-label {
            font-size: 0.75rem;
        }

        .stepper-line {
            margin: 0 0.5rem;
        }
    }

    .address-card {
        padding: 0.75rem 1rem;
        border: 1.5px solid rgba(22,13,12,0.12);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        background: #fff;
        height: 100%;
    }

    .address-card:hover {
        border-color: #ED5F1E;
        background: rgba(237,95,30,0.03);
    }

    .address-card.selected {
        border-color: #ED5F1E;
        background: rgba(237,95,30,0.05);
    }

    .address-card.selected .address-check { display: inline !important; }
</style>
@endpush

@push("scripts")
<script nonce="{{ csp_nonce() }}">
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    var applyBtn    = document.getElementById('checkout-apply-promo');
    var removeBtn   = document.getElementById('checkout-remove-promo');
    var promoInput  = document.getElementById('checkout-promo-input');
    var feedback    = document.getElementById('checkout-promo-feedback');
    var subtotal    = {{ (int)$subtotal }};

    function showFeedback(msg, ok) {
        if (!feedback) return;
        feedback.textContent = msg;
        feedback.style.color = ok ? '#16a34a' : '#dc2626';
        feedback.style.display = 'block';
    }

    if (applyBtn && promoInput) {
        applyBtn.addEventListener('click', function () {
            var code = promoInput.value.trim();
            if (!code) { showFeedback('Entrez un code promo.', false); return; }
            applyBtn.disabled = true;
            fetch('/api/checkout/apply-promo', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({ code: code, total: subtotal }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) { location.reload(); }
                else { showFeedback(data.message || 'Code invalide.', false); applyBtn.disabled = false; }
            })
            .catch(function() { showFeedback('Erreur réseau.', false); applyBtn.disabled = false; });
        });
        promoInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); applyBtn.click(); } });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            fetch('/api/checkout/remove-promo', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({}),
            }).then(function() { location.reload(); }).catch(function() { location.reload(); });
        });
    }
})();

// Address selector
var selectedAddressData = null;
@if($defaultAddress)
selectedAddressData = {
    line1:   '{{ addslashes($defaultAddress->address_line_1) }}',
    line2:   '{{ addslashes($defaultAddress->address_line_2 ?? '') }}',
    city:    '{{ addslashes($defaultAddress->city) }}',
    postal:  '{{ addslashes($defaultAddress->postal_code ?? '') }}',
    country: '{{ addslashes($defaultAddress->country) }}',
};
@endif

function fillFormFromAddress(data) {
    var f = function(id, val) { var el = document.getElementById(id); if (el) el.value = val; };
    f('address_line1', data.line1);
    f('address_line2', data.line2);
    f('city',         data.city);
    f('postal_code',  data.postal);
    f('country',      data.country);
}

function selectAddress(card) {
    document.querySelectorAll('.address-card').forEach(function(c) { c.classList.remove('selected'); });
    card.classList.add('selected');
    selectedAddressData = {
        line1:   card.dataset.line1,
        line2:   card.dataset.line2,
        city:    card.dataset.city,
        postal:  card.dataset.postal,
        country: card.dataset.country,
    };
    var manual = document.getElementById('manual-address-form');
    if (manual) {
        manual.style.display = 'none';
        fillFormFromAddress(selectedAddressData);
    }
}

function toggleManualAddress() {
    var manual = document.getElementById('manual-address-form');
    var btn    = document.getElementById('toggle-manual-btn');
    if (!manual) return;
    if (manual.style.display === 'none') {
        manual.style.display = '';
        document.querySelectorAll('.address-card').forEach(function(c) { c.classList.remove('selected'); });
        if (btn) btn.innerHTML = '<i class="fas fa-times me-1"></i>Annuler';
    } else {
        manual.style.display = 'none';
        if (btn) btn.innerHTML = '<i class="fas fa-plus me-1"></i>Utiliser une autre adresse';
        // Re-select default card if exists
        var first = document.querySelector('.address-card');
        if (first) selectAddress(first);
    }
}

// On page load: if default address exists and selector is shown, pre-fill hidden form
document.addEventListener('DOMContentLoaded', function() {
    var defaultCard = document.querySelector('.address-card.selected');
    if (defaultCard) {
        fillFormFromAddress({
            line1:   defaultCard.dataset.line1,
            line2:   defaultCard.dataset.line2,
            city:    defaultCard.dataset.city,
            postal:  defaultCard.dataset.postal,
            country: defaultCard.dataset.country,
        });
    }
});
</script>
@endpush

@endsection