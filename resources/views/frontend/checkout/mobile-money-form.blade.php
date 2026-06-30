@extends('layouts.frontend')

@section('title', 'Paiement Mobile Money - RACINE BY GANDA')

@if(!empty($recaptchaSiteKey))
@push('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}" defer></script>
@endpush
@endif

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            {{-- Card principale --}}
            <div class="card shadow-lg">
                {{-- Header --}}
                <div class="card-header bg-dark text-white py-4">
                    <h3 class="h4 mb-2">Paiement Mobile Money</h3>
                    <p class="mb-0">
                        Commande #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }} - 
                        <strong>{{ format_price($order->total_amount) }}</strong>
                    </p>
                </div>

                {{-- Contenu --}}
                <div class="card-body p-4">
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <form action="{{ route('checkout.mobile-money.pay', $order) }}" method="POST" id="momo-form">
                        @csrf
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                        
                        {{-- Opérateur --}}
                        <div class="form-group">
                            <label for="provider">Opérateur Mobile Money <span class="text-danger">*</span></label>
                            <select name="provider" id="provider" class="form-control @error('provider') is-invalid @enderror" required>
                                <option value="">Sélectionnez votre opérateur</option>
                                <option value="mtn_momo" {{ old('provider') === 'mtn_momo' ? 'selected' : '' }}>MTN Mobile Money</option>
                                <option value="airtel_money" {{ old('provider') === 'airtel_money' ? 'selected' : '' }}>Airtel Money</option>
                            </select>
                            @error('provider')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Numéro de téléphone --}}
                        <div class="form-group">
                            <label for="phone">Numéro de téléphone <span class="text-danger">*</span></label>
                            <input type="tel" 
                                   name="phone" 
                                   id="phone" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   placeholder="+242 06 XX XX XX" 
                                   value="{{ old('phone', $order->customer_phone) }}" 
                                   required>
                            <small class="form-text text-muted">Entrez le numéro associé à votre compte Mobile Money</small>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Instructions --}}
                        <div class="alert alert-info">
                            <h6 class="font-weight-bold">
                                <i class="fas fa-info-circle me-2"></i>
                                Instructions
                            </h6>
                            <p class="mb-0">Après validation, vous recevrez une demande de paiement sur votre téléphone. Suivez les instructions pour confirmer le paiement.</p>
                        </div>

                        {{-- Bouton submit --}}
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Confirmer le paiement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@if(!empty($recaptchaSiteKey))
@push('scripts')
<script nonce="{{ $cspNonce }}">
document.getElementById('momo-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ $recaptchaSiteKey }}', {action: 'mobile_money_pay'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
            form.submit();
        });
    });
});
</script>
@endpush
@endif

@endsection
