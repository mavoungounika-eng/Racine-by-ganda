@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Finaliser ma commande</h1>
        </div>
    </div>

    <form action="{{ route('checkout.place') }}" method="POST">
        @csrf

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
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-lock mr-2"></i>
                                Valider ma commande
                            </button>
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
