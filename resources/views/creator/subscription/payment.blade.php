@extends('layouts.creator')

@section('title', 'Paiement Abonnement - RACINE BY GANDA')
@section('page-title', 'Paiement')

@push('styles')
<style>
    .payment-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .plan-summary {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .plan-summary h2 {
        font-size: 1.5rem;
        color: var(--racine-black);
        margin-bottom: 1rem;
    }
    
    .plan-summary .price {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--racine-orange);
        margin-bottom: 0.5rem;
    }
    
    .payment-methods {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .payment-method {
        border: 2px solid #E5DDD3;
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: var(--transition-fast);
    }
    
    .payment-method:hover {
        border-color: var(--racine-orange);
        background: rgba(237, 95, 30, 0.05);
    }
    
    .payment-method.selected {
        border-color: var(--racine-orange);
        background: rgba(237, 95, 30, 0.1);
    }
    
    .payment-method-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .payment-method-icon {
        font-size: 2rem;
        color: var(--racine-orange);
    }
    
    .payment-method-title {
        font-weight: 600;
        color: var(--racine-black);
        flex: 1;
    }
    
    .payment-method-desc {
        font-size: 0.875rem;
        color: #8B7355;
        margin-left: 3rem;
    }
    
    .btn-pay {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: var(--transition-fast);
        box-shadow: var(--shadow-orange);
        margin-top: 1.5rem;
    }
    
    .btn-pay:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
    }
    
    .btn-pay:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
<div class="payment-container">
    {{-- PLAN SUMMARY --}}
    <div class="plan-summary">
        <h2>{{ $plan->name }}</h2>
        <div class="price">{{ number_format($plan->price, 0, ',', ' ') }} XAF</div>
        <p style="color: #8B7355; margin: 0;">/ mois</p>
    </div>

    {{-- PAYMENT METHODS --}}
    <div class="payment-methods">
        <h3 style="margin-bottom: 1.5rem; color: var(--racine-black);">Choisissez votre méthode de paiement</h3>
        
        <form id="payment-form" method="POST">
            @csrf
            
            {{-- Carte Bancaire (Stripe) --}}
            <div class="payment-method" onclick="selectPaymentMethod('stripe')">
                <input type="radio" name="payment_method" value="stripe" id="stripe" style="display: none;">
                <div class="payment-method-header">
                    <i class="fas fa-credit-card payment-method-icon"></i>
                    <label for="stripe" class="payment-method-title">Carte bancaire</label>
                </div>
                <p class="payment-method-desc">Paiement sécurisé par Stripe (Visa, Mastercard)</p>
            </div>
            
            {{-- Mobile Money --}}
            <div class="payment-method" onclick="selectPaymentMethod('mobile-money')">
                <input type="radio" name="payment_method" value="mobile-money" id="mobile-money" style="display: none;">
                <div class="payment-method-header">
                    <i class="fas fa-mobile-alt payment-method-icon"></i>
                    <label for="mobile-money" class="payment-method-title">Mobile Money</label>
                </div>
                <p class="payment-method-desc">MTN Mobile Money, Airtel Money</p>
            </div>
            
            <button type="submit" class="btn-pay" id="pay-btn" disabled>
                Payer {{ number_format($plan->price, 0, ',', ' ') }} XAF
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function selectPaymentMethod(method) {
        // Désélectionner toutes les méthodes
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Sélectionner la méthode choisie
        const selected = document.querySelector(`[onclick*="${method}"]`);
        if (selected) {
            selected.classList.add('selected');
            document.getElementById(method).checked = true;
        }
        
        // Activer le bouton de paiement
        document.getElementById('pay-btn').disabled = false;
        
        // Mettre à jour le formulaire
        const form = document.getElementById('payment-form');
        if (method === 'stripe') {
            form.action = '{{ route("creator.subscription.stripe", $plan) }}';
        } else {
            form.action = '{{ route("creator.subscription.mobile-money", $plan) }}';
        }
    }
</script>
@endpush
@endsection

