@extends('layouts.frontend')

@section('title', 'Finaliser ma commande - RACINE BY GANDA')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/checkout-premium.css') }}">
@endpush

@section('content')
<div class="checkout-page py-5" style="background-color:#FFF7F0;">
    <div class="container">
        
        {{-- Messages d'alerte --}}
                @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-left: 4px solid #E74C3C; border-radius: 12px;">
                    <strong><i class="fas fa-exclamation-circle me-2"></i>Erreur !</strong> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif

                @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-left: 4px solid #2ECC71; border-radius: 12px;">
                    <strong><i class="fas fa-check-circle me-2"></i>Succès !</strong> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif

        {{-- Titre --}}
        <h1 class="text-center mb-4" style="font-weight:700; color:#160D0C;">Finaliser ma commande</h1>

        {{-- STEPPER --}}
        <div class="d-flex justify-content-center mb-4">
            <ul class="list-inline mb-0 text-center">
                <li class="list-inline-item mx-3">
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge badge-pill mb-1" style="background:linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color:white; width:40px; height:40px; display:flex; align-items:center; justify-content:center; font-size:1rem;">1</span>
                        <small style="color:#ED5F1E; font-weight:600;">Informations</small>
                    </div>
                </li>
                <li class="list-inline-item mx-3">
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge badge-pill mb-1" style="background:linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color:white; width:40px; height:40px; display:flex; align-items:center; justify-content:center; font-size:1rem;">2</span>
                        <small style="color:#ED5F1E; font-weight:600;">Adresse</small>
                    </div>
                </li>
                <li class="list-inline-item mx-3">
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge badge-pill mb-1" style="background:#E9ECEF; color:#6C757D; width:40px; height:40px; display:flex; align-items:center; justify-content:center; font-size:1rem;">3</span>
                        <small style="color:#6C757D;">Paiement</small>
                    </div>
                </li>
                <li class="list-inline-item mx-3">
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge badge-pill mb-1" style="background:#E9ECEF; color:#6C757D; width:40px; height:40px; display:flex; align-items:center; justify-content:center; font-size:1rem;">4</span>
                        <small style="color:#6C757D;">Validation</small>
                    </div>
                </li>
            </ul>
        </div>

        <form action="{{ route('checkout.place') }}" method="POST" id="checkout-form">
                    @csrf
            <input type="hidden" name="_checkout_token" value="{{ $formToken ?? '' }}">
                    
                    <div class="row">
                {{-- COLONNE GAUCHE --}}
                <div class="col-lg-8 mb-4">

                    {{-- Informations client --}}
                    <div class="card shadow-sm mb-4 border-0 rounded-lg">
                        <div class="card-body">
                            <h4 class="mb-2" style="color:#160D0C;">
                                <i class="fas fa-user-circle me-2" style="color:#ED5F1E;"></i>Informations client
                            </h4>
                            <p class="text-muted mb-4">
                                Ces informations seront utilisées pour vous envoyer la confirmation et vous contacter si besoin.
                            </p>

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="customer_name">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           name="customer_name" 
                                           id="customer_name"
                                           class="form-control @error('customer_name') is-invalid @enderror" 
                                           value="{{ old('customer_name', $user->name ?? '') }}"
                                           required>
                                    @error('customer_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="customer_email">Email <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           name="customer_email" 
                                           id="customer_email"
                                           class="form-control @error('customer_email') is-invalid @enderror" 
                                           value="{{ old('customer_email', $user->email ?? '') }}"
                                           required>
                                    @error('customer_email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="customer_phone">Téléphone (WhatsApp) <span class="text-danger">*</span></label>
                                    <input type="tel" 
                                           name="customer_phone" 
                                           id="customer_phone"
                                           class="form-control @error('customer_phone') is-invalid @enderror" 
                                           value="{{ old('customer_phone', $user->phone ?? ($defaultAddress->phone ?? '')) }}"
                                           required>
                                    @error('customer_phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                                    </div>
                                </div>
                            
                    {{-- Adresse de livraison --}}
                    <div class="card shadow-sm mb-4 border-0 rounded-lg">
                        <div class="card-body">
                            <h4 class="mb-3" style="color:#160D0C;">
                                <i class="fas fa-map-marker-alt me-2" style="color:#ED5F1E;"></i>Adresse de livraison
                            </h4>

                            @if(Auth::check() && $addresses->count() > 0)
                            {{-- Sélection adresse existante --}}
                            <div class="mb-4">
                                <label class="form-label mb-3" style="font-weight:600;">Utiliser une adresse sauvegardée</label>
                                    @foreach($addresses as $address)
                                <div class="mb-3 border rounded-lg p-3" style="cursor:pointer; transition:all 0.3s;" onclick="selectAddress({{ $address->id }})">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="address_id" 
                                                       id="address_{{ $address->id }}" 
                                                       value="{{ $address->id }}"
                                                       {{ ($defaultAddress && $defaultAddress->id === $address->id) ? 'checked' : '' }}
                                               onchange="toggleAddressForm()">
                                        <label class="form-check-label w-100" for="address_{{ $address->id }}">
                                            <strong style="color:#160D0C;">
                                                <i class="fas fa-user me-1" style="color:#ED5F1E;"></i>{{ $address->full_name }}
                                                            </strong>
                                                            @if($address->is_default)
                                                <span class="badge ml-2" style="background:linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color:white;">
                                                                    <i class="fas fa-star me-1"></i>Par défaut
                                                                </span>
                                                            @endif
                                            <p class="text-muted mb-1 small">
                                                <i class="fas fa-map-marker-alt me-1" style="color:#ED5F1E;"></i>{{ $address->full_address }}
                                                            </p>
                                                            @if($address->phone)
                                                                <p class="text-muted mb-0 small">
                                                    <i class="fas fa-phone me-1" style="color:#ED5F1E;"></i>{{ $address->phone }}
                                                                </p>
                                                            @endif
                                                </label>
                                        </div>
                                    </div>
                                    @endforeach
                                <div class="mb-3 border border-dashed rounded-lg p-3" style="cursor:pointer; border-color:#ED5F1E !important; background:rgba(237,95,30,0.05);" onclick="selectNewAddress()">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="address_id" 
                                               id="address_new" 
                                               value="" 
                                               onchange="toggleAddressForm()">
                                        <label class="form-check-label w-100" for="address_new">
                                            <strong style="color:#ED5F1E;">
                                                <i class="fas fa-plus-circle me-1"></i>Utiliser une nouvelle adresse
                                                    </strong>
                                                </label>
                                            </div>
                                        </div>
                                <a href="{{ route('profile.addresses') }}" class="text-decoration-none" style="color:#ED5F1E;">
                                    <i class="fas fa-cog me-1"></i>Gérer mes adresses
                                </a>
                            </div>
                            @endif

                            {{-- Formulaire adresse (masqué si adresse sélectionnée) --}}
                            <div id="address-form" style="{{ (Auth::check() && $addresses->count() > 0 && $defaultAddress) ? 'display: none;' : '' }}">
                                    @if(Auth::check())
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="new_address_first_name">Prénom <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               name="new_address_first_name" 
                                               id="new_address_first_name"
                                                   class="form-control" 
                                                   value="{{ old('new_address_first_name') }}">
                                        </div>
                                    <div class="form-group col-md-6">
                                        <label for="new_address_last_name">Nom <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               name="new_address_last_name" 
                                               id="new_address_last_name"
                                                   class="form-control" 
                                                   value="{{ old('new_address_last_name') }}">
                                        </div>
                                    </div>

                                        <div class="form-group">
                                    <label for="new_address_line_1">Adresse ligne 1 <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           name="new_address_line_1" 
                                           id="new_address_line_1"
                                                   class="form-control" 
                                                   value="{{ old('new_address_line_1') }}">
                                        </div>

                                        <div class="form-group">
                                    <label for="new_address_line_2">Adresse ligne 2 (optionnel)</label>
                                    <input type="text" 
                                           name="new_address_line_2" 
                                           id="new_address_line_2"
                                                   class="form-control" 
                                                   value="{{ old('new_address_line_2') }}">
                                        </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="new_address_city">Ville <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               name="new_address_city" 
                                               id="new_address_city"
                                                   class="form-control" 
                                                   value="{{ old('new_address_city') }}">
                                        </div>
                                    <div class="form-group col-md-3">
                                            <label for="new_address_postal_code">Code postal</label>
                                        <input type="text" 
                                               name="new_address_postal_code" 
                                               id="new_address_postal_code"
                                                   class="form-control" 
                                                   value="{{ old('new_address_postal_code') }}">
                                        </div>
                                    <div class="form-group col-md-3">
                                        <label for="new_address_country">Pays <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               name="new_address_country" 
                                               id="new_address_country"
                                                   class="form-control" 
                                                   value="{{ old('new_address_country', 'Congo') }}">
                                        </div>
                                    </div>

                                        <div class="form-group">
                                            <label for="new_address_phone">Téléphone</label>
                                    <input type="tel" 
                                           name="new_address_phone" 
                                           id="new_address_phone"
                                                   class="form-control" 
                                                   value="{{ old('new_address_phone', $user->phone ?? '') }}">
                                        </div>

                                <div class="form-group form-check">
                                    <input type="checkbox" 
                                           name="save_new_address" 
                                           id="save_new_address"
                                           class="form-check-input" 
                                           value="1">
                                            <label class="form-check-label" for="save_new_address">
                                                Sauvegarder cette adresse pour les prochaines commandes
                                            </label>
                                    </div>
                                    @else
                                        <div class="form-group">
                                    <label for="customer_address">Adresse de livraison <span class="text-danger">*</span></label>
                                    <textarea name="customer_address" 
                                              id="customer_address"
                                                      class="form-control @error('customer_address') is-invalid @enderror" 
                                                      rows="3" 
                                                      required>{{ old('customer_address') }}</textarea>
                                            @error('customer_address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                    </div>
                                    @endif

                                <div class="form-group mb-0">
                                        <label for="notes">Notes de commande (optionnel)</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              rows="3"
                                                  class="form-control" 
                                              placeholder="Instructions spéciales, préférences de livraison…">{{ old('notes') }}</textarea>
                                </div>
                                    </div>
                                </div>
                            </div>

                    {{-- Livraison & Paiement --}}
                    <div class="card shadow-sm mb-4 border-0 rounded-lg">
                        <div class="card-body">
                            <h4 class="mb-3" style="color:#160D0C;">
                                <i class="fas fa-truck me-2" style="color:#ED5F1E;"></i>Livraison & Paiement
                            </h4>

                            {{-- Options de livraison --}}
                            <h5 class="mb-3" style="font-weight:600;">Options de livraison</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="w-100" style="cursor:pointer;">
                                        <input type="radio" 
                                               name="shipping_method" 
                                               value="standard"
                                               id="shipping_standard"
                                               class="d-none" 
                                               checked
                                               onchange="updateShippingCost(5900)">
                                        <div class="border rounded-lg p-3 h-100 delivery-option-card" style="transition:all 0.3s;">
                                            <div class="text-center mb-2">
                                                <i class="fas fa-box" style="font-size:2rem; color:#ED5F1E;"></i>
                                            </div>
                                            <div class="font-weight-bold text-center">Standard</div>
                                            <small class="text-muted d-block text-center">5–7 jours ouvrés</small>
                                            <div class="mt-2 font-weight-bold text-center" style="color:#ED5F1E;">5 900 FCFA</div>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="w-100" style="cursor:pointer;">
                                        <input type="radio" 
                                               name="shipping_method" 
                                               value="express"
                                               id="shipping_express"
                                               class="d-none"
                                               onchange="updateShippingCost(9900)">
                                        <div class="border rounded-lg p-3 h-100 delivery-option-card" style="transition:all 0.3s;">
                                            <div class="text-center mb-2">
                                                <i class="fas fa-truck" style="font-size:2rem; color:#ED5F1E;"></i>
                                            </div>
                                            <div class="font-weight-bold text-center">Express</div>
                                            <small class="text-muted d-block text-center">2–3 jours ouvrés</small>
                                            <div class="mt-2 font-weight-bold text-center" style="color:#ED5F1E;">9 900 FCFA</div>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="w-100" style="cursor:pointer;">
                                        <input type="radio" 
                                               name="shipping_method" 
                                               value="relay"
                                               id="shipping_relay"
                                               class="d-none"
                                               onchange="updateShippingCost(3900)">
                                        <div class="border rounded-lg p-3 h-100 delivery-option-card" style="transition:all 0.3s;">
                                            <div class="text-center mb-2">
                                                <i class="fas fa-store" style="font-size:2rem; color:#ED5F1E;"></i>
                                            </div>
                                            <div class="font-weight-bold text-center">Point relais</div>
                                            <small class="text-muted d-block text-center">4–6 jours ouvrés</small>
                                            <div class="mt-2 font-weight-bold text-center" style="color:#ED5F1E;">3 900 FCFA</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <input type="hidden" id="shipping_cost" name="shipping_cost" value="5900">

                            <hr>

                            {{-- Modes de paiement --}}
                            <h5 class="mb-3" style="font-weight:600;">Mode de paiement</h5>
                            
                                @if(config('stripe.enabled'))
                            <div class="form-group mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="payment_method" 
                                           id="payment_card" 
                                           value="card" 
                                           checked>
                                    <label class="form-check-label w-100" for="payment_card">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-credit-card me-2" style="color:#ED5F1E; font-size:1.25rem;"></i>
                                            <div>
                                            <strong>Carte Bancaire</strong>
                                                <small class="d-block text-muted">Paiement sécurisé via Stripe</small>
                                    </div>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2" style="padding-left:2rem;">
                                            <i class="fas fa-lock me-1"></i>
                                            Paiement 100% sécurisé. Vos données bancaires ne sont jamais stockées.
                                        </p>
                                    </label>
                                    </div>
                                </div>
                                @endif

                            <div class="form-group mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="payment_method" 
                                           id="payment_mobile" 
                                           value="mobile_money"
                                           {{ !config('stripe.enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label w-100" for="payment_mobile">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-mobile-alt me-2" style="color:#ED5F1E; font-size:1.25rem;"></i>
                                            <div>
                                            <strong>Mobile Money</strong>
                                                <small class="d-block text-muted">MTN MoMo, Airtel Money</small>
                                    </div>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2" style="padding-left:2rem;">
                                            Vous serez redirigé vers une page de paiement sécurisée pour valider votre transaction.
                                        </p>
                                    </label>
                                    </div>
                                </div>

                            <div class="form-group mb-0">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="payment_method" 
                                           id="payment_cash" 
                                           value="cash">
                                    <label class="form-check-label w-100" for="payment_cash">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-money-bill-wave me-2" style="color:#ED5F1E; font-size:1.25rem;"></i>
                                            <div>
                                            <strong>Paiement à la livraison</strong>
                                                <small class="d-block text-muted">Espèces uniquement</small>
                                    </div>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2" style="padding-left:2rem;">
                                            Payez en espèces lors de la réception de votre commande.
                                        </p>
                                    </label>
                                </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                {{-- COLONNE DROITE : RÉSUMÉ --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-lg sticky-top" style="top: 100px;">
                        <div class="card-body">
                            <h4 class="mb-3" style="color:#160D0C;">Résumé de la commande</h4>
                                
                                @if($items->count() > 0)
                            {{-- Liste des produits --}}
                                        @foreach($items as $item)
                                            @php
                                                $product = Auth::check() ? $item->product : \App\Models\Product::find($item['product_id']);
                                                $quantity = Auth::check() ? $item->quantity : $item['quantity'];
                                                $price = Auth::check() ? $item->price : $item['price'];
                                                $subtotal = $price * $quantity;
                                    $mainImage = $product ? $product->main_image : null;
                                            @endphp
                                <div class="d-flex mb-3">
                                    <div class="mr-3" style="width:60px;height:60px;overflow:hidden;border-radius:8px;flex-shrink:0;">
                                        <img src="{{ $mainImage ? asset('storage/' . $mainImage) : 'https://via.placeholder.com/60' }}"
                                             alt="{{ $product->title ?? 'Produit' }}" 
                                             class="img-fluid" 
                                             style="width:100%;height:100%;object-fit:cover;">
                                                </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold" style="font-size:0.9rem;">
                                            {{ $product->title ?? 'Produit' }}
                                                </div>
                                        <small class="text-muted">
                                            Qté : {{ $quantity }} × {{ number_format($price,0,',',' ') }} FCFA
                                        </small>
                                            </div>
                                    <div class="ml-2">
                                        <strong style="font-size:0.95rem;">{{ number_format($subtotal,0,',',' ') }} FCFA</strong>
                                    </div>
                                        </div>
                            @endforeach

                            <a href="{{ route('cart.index') }}" class="btn btn-link p-0 mb-3" style="color:#ED5F1E;">
                                <i class="fas fa-edit me-1"></i>Modifier le panier
                            </a>

                            <hr>

                            {{-- Code promo --}}
                            <div class="mb-3">
                                <label for="promo_code" class="mb-1" style="font-weight:600;">
                                    <i class="fas fa-tag me-1" style="color:#ED5F1E;"></i>Code promo
                                        </label>
                                        <div class="input-group">
                                    <input type="text" 
                                           name="promo_code" 
                                           id="promo_code" 
                                           class="form-control" 
                                           placeholder="ENTREZ VOTRE CODE"
                                           style="text-transform:uppercase;">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                id="apply-promo-btn"
                                                style="border-color:#ED5F1E; color:#ED5F1E;">
                                                Appliquer
                                            </button>
                                    </div>
                                        </div>
                                        <div id="promo-message" class="mt-2 small"></div>
                                <input type="hidden" id="promo_code_id" name="promo_code_id" value="">
                                <input type="hidden" id="discount_amount" name="discount_amount" value="0">
                                    </div>

                            <hr>

                            {{-- Totaux --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span>Sous-total</span>
                                <span id="subtotal-display">{{ number_format($total,0,',',' ') }} FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1" id="discount-row" style="display:none;">
                                <span class="text-success">
                                    <i class="fas fa-tag me-1"></i>Réduction
                                </span>
                                <span class="text-success" id="discount-display">- 0 FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Livraison</span>
                                <span id="shipping-display">5 900 FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2 mb-3 font-weight-bold" style="font-size:1.1rem;">
                                <span>Total TTC</span>
                                <span id="total-display">{{ number_format($total + 5900,0,',',' ') }} FCFA</span>
                                    </div>

                            <div class="form-group form-check mb-3">
                                <input type="checkbox" 
                                       name="accept_terms" 
                                       id="accept_terms" 
                                       class="form-check-input" 
                                       required>
                                <label class="form-check-label" for="accept_terms">
                                    J'accepte les <a href="#" id="show-terms-link" style="color:#ED5F1E;">conditions générales de vente</a>
                                        </label>
                            </div>

                            <button type="submit" 
                                    class="btn btn-block btn-lg text-uppercase font-weight-bold" 
                                    id="submit-order-btn"
                                    style="background:linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color:white; border:none; border-radius:999px; padding:1rem;">
                                <span id="submit-text">
                                    <i class="fas fa-lock me-2"></i>Valider ma commande
                                </span>
                                <span id="submit-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                            </button>

                            <div class="mt-3 small text-muted text-center">
                                <div class="mb-1"><i class="fas fa-lock mr-1" style="color:#ED5F1E;"></i> Paiement sécurisé</div>
                                <div class="mb-1"><i class="fas fa-box mr-1" style="color:#ED5F1E;"></i> Livraison suivie</div>
                                <div><i class="fas fa-comments mr-1" style="color:#ED5F1E;"></i> Support WhatsApp</div>
                            </div>
                                @else
                            <div class="text-center py-4">
                                <p>Votre panier est vide.</p>
                                <a href="{{ route('frontend.shop') }}" class="btn" style="background:linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color:white; border:none;">
                                            Continuer mes achats
                                        </a>
                                    </div>
                                @endif
                        </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => 'Panier', 'url' => route('cart.index')],
        ['label' => 'Commande', 'url' => null],
    ],
    'backUrl' => route('cart.index'),
    'backText' => 'Retour au panier',
    'position' => 'bottom',
])
@endsection

@push('scripts')
{{-- Scripts existants conservés --}}
<script>
    // ============================================
    // VALIDATION TEMPS RÉEL
    // ============================================
    
    const emailInput = document.getElementById('customer_email');
    if (emailInput) {
        let emailTimeout;
        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            const email = this.value;
            if (email.length === 0) {
                removeValidationFeedback(this);
                return;
            }
            emailTimeout = setTimeout(() => validateEmail(email, this), 500);
        });
    }
    
    const phoneInput = document.getElementById('customer_phone');
    if (phoneInput) {
        let phoneTimeout;
        phoneInput.addEventListener('input', function() {
            clearTimeout(phoneTimeout);
            const phone = this.value;
            if (phone.length === 0) {
                removeValidationFeedback(this);
                return;
            }
            phoneTimeout = setTimeout(() => validatePhone(phone, this), 500);
        });
    }
    
    function validateEmail(email, input) {
        fetch('{{ route("api.checkout.validate-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(res => res.json())
        .then(data => {
            if (data.valid) {
                showValidationSuccess(input);
            } else {
                showValidationError(input, data.errors[0] || 'Email invalide');
            }
        })
        .catch(error => console.error('Erreur validation email:', error));
    }
    
    function validatePhone(phone, input) {
        fetch('{{ route("api.checkout.validate-phone") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ phone: phone })
        })
        .then(res => res.json())
        .then(data => {
            if (data.valid) {
                showValidationSuccess(input);
            } else {
                showValidationError(input, data.errors[0] || 'Téléphone invalide');
            }
        })
        .catch(error => console.error('Erreur validation téléphone:', error));
    }
    
    function showValidationSuccess(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }
    
    function showValidationError(input, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        let feedback = input.parentElement.querySelector('.validation-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'validation-feedback invalid-feedback d-block';
            input.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    }
    
    function removeValidationFeedback(input) {
        input.classList.remove('is-valid', 'is-invalid');
        const feedback = input.parentElement.querySelector('.validation-feedback');
        if (feedback) feedback.remove();
    }
    
    // ============================================
    // VÉRIFICATION STOCK
    // ============================================
    
    // ============================================
    // VÉRIFICATION STOCK AVANT SOUMISSION
    // ============================================
    // En cas d'erreur réseau, on bloque la soumission et on affiche un message clair à l'utilisateur.
    // Objectif : Jamais autoriser la soumission si la vérification n'a pas abouti correctement.
    // ============================================
    function verifyStockBeforeSubmit() {
        return fetch('{{ route("api.checkout.verify-stock") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            // Vérifier que la réponse est OK
            if (!res.ok) {
                throw new Error(`Erreur serveur: ${res.status} ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.has_issues) {
                showStockIssuesModal(data.issues);
                return false;
            }
            return true;
        })
        .catch(error => {
            // En cas d'erreur réseau (timeout, serveur, exception), on bloque la soumission
            console.error('Erreur vérification stock:', error);
            
            // Afficher message clair à l'utilisateur
            alert('Une erreur est survenue lors de la vérification du stock. Vérifiez votre connexion ou réessayez.');
            
            // Retourner false pour bloquer la soumission
            return false;
        });
    }
    
    function showStockIssuesModal(issues) {
        let message = '<div class="alert alert-warning"><h5><i class="fas fa-exclamation-triangle me-2"></i>Problèmes de stock détectés</h5><ul class="mb-0">';
        issues.forEach(issue => {
            message += `<li>${issue.product_name}: ${issue.message}</li>`;
        });
        message += '</ul></div><p class="mb-0">Veuillez mettre à jour votre panier avant de continuer.</p>';
        
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'stockIssuesModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Problèmes de stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">${message}</div>
                    <div class="modal-footer">
                        <a href="{{ route('cart.index') }}" class="btn btn-primary">Mettre à jour le panier</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    }
    
    // ============================================
    // GESTION ADRESSES
    // ============================================
    
    function selectAddress(addressId) {
        document.getElementById('address_' + addressId).checked = true;
        toggleAddressForm();
    }

    function selectNewAddress() {
        document.getElementById('address_new').checked = true;
        toggleAddressForm();
    }

    function toggleAddressForm() {
        const addressIdInput = document.querySelector('input[name="address_id"]:checked');
        const addressForm = document.getElementById('address-form');
        if (addressIdInput && addressIdInput.value !== '') {
            addressForm.style.display = 'none';
            addressForm.querySelectorAll('input[required], textarea[required]').forEach(field => {
                field.removeAttribute('required');
            });
        } else {
            addressForm.style.display = 'block';
            addressForm.querySelectorAll('input, textarea').forEach(field => {
                if (field.name && (field.name.startsWith('customer_') || field.name.startsWith('new_address_'))) {
                    if (field.name === 'customer_name' || field.name === 'customer_email' || 
                        field.name === 'customer_phone' || field.name === 'new_address_line_1' || 
                        field.name === 'new_address_city' || field.name === 'new_address_country') {
                        field.setAttribute('required', 'required');
                    }
                }
            });
        }
    }

    // ============================================
    // GESTION LIVRAISON
    // ============================================
    
    function updateShippingCost(cost) {
        document.getElementById('shipping_cost').value = cost;
        updateTotals(parseFloat(document.getElementById('discount_amount').value || 0), false);
        
        // Mettre à jour l'affichage
        document.getElementById('shipping-display').textContent = new Intl.NumberFormat('fr-FR').format(cost) + ' FCFA';
        
        // Mettre à jour le style des cards
        document.querySelectorAll('.delivery-option-card').forEach(card => {
            card.style.borderColor = '#E9ECEF';
            card.style.background = 'white';
        });
        const selectedCard = document.querySelector(`input[name="shipping_method"]:checked`).closest('label').querySelector('.delivery-option-card');
        if (selectedCard) {
            selectedCard.style.borderColor = '#ED5F1E';
            selectedCard.style.background = 'rgba(237, 95, 30, 0.05)';
        }
    }
    
    // Gestion clic sur cards livraison
    document.querySelectorAll('.delivery-option-card').forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.closest('label').querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                const costs = { standard: 5900, express: 9900, relay: 3900 };
                updateShippingCost(costs[radio.value]);
                }
            });
        });

    // ============================================
    // GESTION CODE PROMO
    // ============================================
    
    const applyPromoBtn = document.getElementById('apply-promo-btn');
    const promoCodeInput = document.getElementById('promo_code');
    const promoMessage = document.getElementById('promo-message');
    const promoCodeIdInput = document.getElementById('promo_code_id');
    const discountAmountInput = document.getElementById('discount_amount');
    let appliedPromoCode = null;
    
    if (applyPromoBtn && promoCodeInput) {
        applyPromoBtn.addEventListener('click', function() {
            const code = promoCodeInput.value.trim().toUpperCase();
            if (!code) {
                promoMessage.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Veuillez entrer un code promo</span>';
                return;
            }
            applyPromoBtn.disabled = true;
            applyPromoBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Vérification...';
            fetch('{{ route("api.checkout.apply-promo") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code, total: {{ $total }} })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    appliedPromoCode = data.promo_code;
                    promoCodeIdInput.value = data.promo_code.id;
                    discountAmountInput.value = data.discount_amount;
                    promoMessage.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>${data.message}</span>`;
                    promoCodeInput.style.borderColor = '#22C55E';
                    promoCodeInput.disabled = true;
                    applyPromoBtn.innerHTML = '<i class="fas fa-times me-1"></i>Retirer';
                    applyPromoBtn.onclick = removePromoCode;
                    updateTotals(data.discount_amount, data.free_shipping);
                } else {
                    promoMessage.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle me-1"></i>${data.message}</span>`;
                    promoCodeInput.style.borderColor = '#E74C3C';
                }
                applyPromoBtn.disabled = false;
            })
            .catch(error => {
                console.error('Erreur:', error);
                promoMessage.innerHTML = '<span class="text-danger">Erreur lors de la vérification du code</span>';
                applyPromoBtn.disabled = false;
                applyPromoBtn.innerHTML = 'Appliquer';
            });
        });
        
        promoCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyPromoBtn.click();
            }
        });
    }
    
    function removePromoCode() {
        appliedPromoCode = null;
        promoCodeIdInput.value = '';
        discountAmountInput.value = 0;
        promoCodeInput.value = '';
        promoCodeInput.disabled = false;
        promoCodeInput.style.borderColor = '';
        promoMessage.innerHTML = '';
        applyPromoBtn.innerHTML = 'Appliquer';
        applyPromoBtn.onclick = null;
        updateTotals(0, false);
    }
    
    function updateTotals(discount, freeShipping) {
        const subtotal = {{ $total }};
        const shippingCost = freeShipping ? 0 : parseFloat(document.getElementById('shipping_cost').value || 5900);
        const total = subtotal - discount + shippingCost;
        document.getElementById('subtotal-display').textContent = new Intl.NumberFormat('fr-FR').format(subtotal) + ' FCFA';
        if (discount > 0) {
            document.getElementById('discount-row').style.display = 'flex';
            document.getElementById('discount-display').textContent = '- ' + new Intl.NumberFormat('fr-FR').format(discount) + ' FCFA';
        } else {
            document.getElementById('discount-row').style.display = 'none';
        }
        document.getElementById('shipping-display').textContent = freeShipping ? 'Gratuite' : new Intl.NumberFormat('fr-FR').format(shippingCost) + ' FCFA';
        document.getElementById('total-display').textContent = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
    }
    
    // ============================================
    // MODAL CGV
    // ============================================
    
    const showTermsLink = document.getElementById('show-terms-link');
    if (showTermsLink) {
        showTermsLink.addEventListener('click', function(e) {
            e.preventDefault();
            showTermsModal();
        });
    }
    
    function showTermsModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'termsModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-file-contract me-2" style="color:#ED5F1E;"></i>Conditions Générales de Vente</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body" style="max-height:60vh; overflow-y:auto;">
                        <h6>1. Objet</h6>
                        <p>Les présentes conditions générales de vente régissent les relations entre RACINE BY GANDA et ses clients.</p>
                        <h6>2. Commandes</h6>
                        <p>Toute commande implique l'acceptation sans réserve des présentes conditions générales de vente.</p>
                        <h6>3. Prix</h6>
                        <p>Les prix sont indiqués en FCFA, toutes taxes comprises. RACINE BY GANDA se réserve le droit de modifier ses prix à tout moment.</p>
                        <h6>4. Paiement</h6>
                        <p>Le paiement s'effectue par carte bancaire, mobile money ou à la livraison. Le paiement est exigible immédiatement.</p>
                        <h6>5. Livraison</h6>
                        <p>Les délais de livraison sont indiqués à titre indicatif. RACINE BY GANDA ne saurait être tenu responsable des retards de livraison.</p>
                        <h6>6. Droit de rétractation</h6>
                        <p>Conformément à la législation en vigueur, vous disposez d'un délai de 14 jours pour exercer votre droit de rétractation.</p>
                        <h6>7. Garanties</h6>
                        <p>RACINE BY GANDA garantit la conformité des produits aux descriptions fournies.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" onclick="acceptTerms()">
                            <i class="fas fa-check me-1"></i>J'accepte
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        $(modal).modal('show');
        $(modal).on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }
    
    function acceptTerms() {
        document.getElementById('accept_terms').checked = true;
        $('#termsModal').modal('hide');
    }
        
        // ============================================
    // SOUMISSION FORMULAIRE
        // ============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        toggleAddressForm();
        document.querySelectorAll('input[name="address_id"]').forEach(radio => {
            radio.addEventListener('change', toggleAddressForm);
        });
        
        // Initialiser style cards livraison
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        if (selectedShipping) {
            const selectedCard = selectedShipping.closest('label').querySelector('.delivery-option-card');
            if (selectedCard) {
                selectedCard.style.borderColor = '#ED5F1E';
                selectedCard.style.background = 'rgba(237, 95, 30, 0.05)';
            }
        }
        
        const checkoutForm = document.getElementById('checkout-form');
        const submitBtn = document.getElementById('submit-order-btn');
        const submitText = document.getElementById('submit-text');
        const submitLoader = document.getElementById('submit-loader');
        
        // Protection contre double soumission
        let isSubmitting = false;
        let formSubmitted = false; // Flag pour indiquer que le formulaire est en cours de soumission normale
        let isRedirecting = false; // Flag pour distinguer navigation normale vs abandon de page
        
        // Désactiver bouton au clic (pas seulement au submit)
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', async function(e) {
                // Protection double soumission
                if (isSubmitting) {
                e.preventDefault();
                    return false;
                }
                
                e.preventDefault();
                
                const acceptTerms = document.getElementById('accept_terms');
                if (!acceptTerms || !acceptTerms.checked) {
                    alert('Veuillez accepter les conditions générales de vente.');
                    return;
                }
                
                // Marquer comme en cours de soumission
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.style.cursor = 'not-allowed';
                submitBtn.style.opacity = '0.7';
                submitText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Vérification en cours...';
                submitLoader.classList.remove('d-none');
                
                try {
                    // Vérification stock avant soumission
                    const stockOk = await verifyStockBeforeSubmit();
                    
                    // Si vérification échoue (stock insuffisant OU erreur réseau), bloquer la soumission
                    if (!stockOk) {
                        // Réactiver le bouton pour permettre à l'utilisateur de réessayer
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.style.cursor = 'pointer';
                        submitBtn.style.opacity = '1';
                        submitText.innerHTML = '<i class="fas fa-lock me-2"></i>Valider ma commande';
                        submitLoader.classList.add('d-none');
                        return; // Bloquer la soumission
                    }
                    
                    // Si vérification OK, continuer avec la soumission
                    submitText.innerHTML = '<i class="fas fa-lock me-2"></i>Validation...';
                    
                    // Marquer que le formulaire va être soumis normalement (pour éviter le modal beforeunload)
                    formSubmitted = true;
                    isRedirecting = true; // Navigation normale après soumission réussie
                    
                    // Soumettre le formulaire
                    this.submit();
                } catch (error) {
                    // Réactiver en cas d'erreur inattendue
                    console.error('Erreur lors de la soumission:', error);
                    isSubmitting = false;
                    formSubmitted = false;
                    isRedirecting = false;
                    submitBtn.disabled = false;
                    submitBtn.style.cursor = 'pointer';
                    submitBtn.style.opacity = '1';
                    submitText.innerHTML = '<i class="fas fa-lock me-2"></i>Valider ma commande';
                    submitLoader.classList.add('d-none');
                    
                    // Afficher message d'erreur
                    alert('Une erreur est survenue. Veuillez réessayer.');
                }
            });
        }
        
        verifyStockBeforeSubmit().then(ok => {
            if (!ok) console.warn('Problèmes de stock détectés au chargement');
        });
        
        // ============================================
        // GESTION beforeunload POUR ÉVITER LA POPUP LORS D'UNE SOUMISSION NORMALE
        // ============================================
        // Objectif : Le modal "Quitter le site ?" ne doit apparaître QUE si l'utilisateur
        // essaie de quitter pendant une action critique (vérification stock ou soumission en cours),
        // MAIS PAS lors d'une soumission normale du formulaire ou d'une redirection après paiement.
        //
        // Logique :
        // - formSubmitted = true : Formulaire validé et en cours de soumission normale
        // - isRedirecting = true : Navigation normale après soumission réussie
        // - isSubmitting = true : Action critique en cours (vérification stock, soumission)
        //
        // Le modal ne s'affiche que si : isSubmitting === true ET (!formSubmitted && !isRedirecting)
        // ============================================
        window.addEventListener('beforeunload', function(e) {
            // Ne pas afficher le modal si :
            // 1. Le formulaire est en cours de soumission normale (formSubmitted = true)
            // 2. Ou si on est en train de rediriger normalement (isRedirecting = true)
            // 3. Ou si aucune soumission n'est en cours (isSubmitting = false)
            if (formSubmitted || isRedirecting || !isSubmitting) {
                return; // Laisser la navigation se faire normalement
            }
            
            // Afficher le modal seulement si l'utilisateur essaie de quitter pendant une action critique
            // (ex: vérification stock en cours, ou soumission bloquée)
            e.preventDefault();
            e.returnValue = 'Une commande est en cours de traitement. Êtes-vous sûr de vouloir quitter ?';
            return e.returnValue;
        });
        
        // Gestion erreur 429 si elle se produit
        window.addEventListener('unhandledrejection', function(event) {
            if (event.reason && event.reason.status === 429) {
                isSubmitting = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.cursor = 'pointer';
                    submitBtn.style.opacity = '1';
                    if (submitText) submitText.innerHTML = '<i class="fas fa-lock me-2"></i>Valider ma commande';
                    if (submitLoader) submitLoader.classList.add('d-none');
                }
                alert('Trop de tentatives. Veuillez patienter quelques instants avant de réessayer.');
            }
        });
    });
</script>

<style>
    /* Styles additionnels pour les cards livraison */
    .delivery-option-card:hover {
        border-color: #ED5F1E !important;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.15);
        transform: translateY(-2px);
    }
    
    /* Style pour les adresses sélectionnables */
    .border:hover {
        border-color: #ED5F1E !important;
    }
    
    /* Responsive stepper */
    @media (max-width: 768px) {
        .list-inline-item {
            margin: 0.5rem !important;
        }
    }
</style>
@endpush
