@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    {{-- Messages flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Erreur de validation :</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- En-tête avec stepper --}}
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-4" style="color: #1c1412; font-weight: 700; letter-spacing: 0.05em;">Finaliser ma commande</h1>
            
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
                        <div class="form-group">
                            <label for="address_line1">Adresse <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('address_line1') is-invalid @enderror" 
                                   id="address_line1" 
                                   name="address_line1" 
                                   value="{{ old('address_line1') }}" 
                                   placeholder="Rue, numéro, quartier" 
                                   required>
                            @error('address_line1')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city">Ville <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('city') is-invalid @enderror" 
                                           id="city" 
                                           name="city" 
                                           value="{{ old('city') }}" 
                                           required>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
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
                                <strong>Livraison à domicile</strong> – 2 000 FCFA
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
                                    $image = Auth::check() ? $product->main_image : $item['main_image'];
                                    $title = Auth::check() ? $product->title : $item['title'];
                                @endphp
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
                                    <div class="flex-grow-1 ml-3">
                                        <h6 class="mb-1">{{ $title }}</h6>
                                        <small class="text-muted">Qté : {{ $qty }}</small>
                                        <div class="mt-1">
                                            <strong>{{ number_format($price * $qty, 0, ',', ' ') }} FCFA</strong>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total</span>
                                <strong>{{ number_format($subtotal, 0, ',', ' ') }} FCFA</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Livraison</span>
                                <strong id="shipping-cost-display">{{ number_format($shipping_default, 0, ',', ' ') }} FCFA</strong>
                            </div>
                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between">
                                    <strong>Total</strong>
                                    <strong class="text-primary" id="total-display">{{ number_format($subtotal + $shipping_default, 0, ',', ' ') }} FCFA</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg btn-block checkout-submit-btn">
                                <i class="fas fa-lock mr-2"></i>
                                Valider ma commande
                            </button>
                            <p class="text-center text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Paiement 100% sécurisé
                            </p>
                        </div>

                        {{-- Support / Contact --}}
                        <div class="mt-4 pt-4 border-top">
                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Besoin d'aide ?</small>
                                <a href="{{ route('frontend.contact') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-envelope mr-1"></i>
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
<style>
    /* Stepper visuel */
    .checkout-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 600px;
        margin: 0 auto;
        padding: 1.5rem;
        background: #f8f9fa;
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
        background: #e9ecef;
        color: #6c757d;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
        transition: all 0.3s;
    }

    .stepper-item.active .stepper-icon {
        background: linear-gradient(135deg, #ED5F1E 0%, #D4A574 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }

    .stepper-item.completed .stepper-icon {
        background: #28a745;
        color: white;
    }

    .stepper-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6c757d;
        text-align: center;
    }

    .stepper-item.active .stepper-label {
        color: #ED5F1E;
    }

    .stepper-item.completed .stepper-label {
        color: #28a745;
    }

    .stepper-line {
        flex: 1;
        height: 2px;
        background: #e9ecef;
        margin: 0 1rem;
        position: relative;
        top: -25px;
    }

    .stepper-item.completed + .stepper-line {
        background: #28a745;
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
        background: linear-gradient(135deg, #ED5F1E 0%, #D4A574 100%) !important;
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
</style>
@endpush

@push('scripts')
<script>
    // Mise à jour dynamique du coût de livraison et du total
    document.addEventListener('DOMContentLoaded', function() {
        const shippingInputs = document.querySelectorAll('input[name="shipping_method"]');
        const shippingDisplay = document.getElementById('shipping-cost-display');
        const totalDisplay = document.getElementById('total-display');
        const subtotal = {{ $subtotal }};

        shippingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const shipping = this.value === 'home_delivery' ? 2000 : 0;
                const total = subtotal + shipping;
                
                shippingDisplay.textContent = shipping.toLocaleString('fr-FR') + ' FCFA';
                totalDisplay.textContent = total.toLocaleString('fr-FR') + ' FCFA';
            });
        });
    });
</script>
@endpush
@endsection
