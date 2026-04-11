@extends('layouts.creator')

@section('title', 'Nos offres d\'abonnement - RACINE BY GANDA')
@section('page-title', 'Abonnements')

@push('styles')
<style>
    .pricing-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .pricing-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .pricing-header h2 {
        font-size: 2rem;
        color: var(--racine-black);
        margin-bottom: 1rem;
    }
    
    .pricing-header p {
        color: #8B7355;
        font-size: 1.1rem;
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        align-items: stretch;
    }
    
    .plan-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        transition: var(--transition-normal);
        position: relative;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .plan-card.current {
        border: 2px solid var(--racine-orange);
    }
    
    .plan-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--racine-black);
        margin-bottom: 0.5rem;
    }
    
    .plan-price {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--racine-orange);
        margin-bottom: 1.5rem;
    }
    
    .plan-price span {
        font-size: 1rem;
        font-weight: 400;
        color: #8B7355;
    }
    
    .plan-features {
        margin-bottom: 2rem;
        flex: 1;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        color: var(--racine-black);
    }
    
    .feature-icon {
        color: var(--racine-green);
        margin-right: 0.75rem;
    }
    
    .btn-plan {
        display: block;
        width: 100%;
        padding: 1rem;
        text-align: center;
        border-radius: var(--radius-lg);
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition-fast);
        border: none;
        cursor: pointer;
    }
    
    .btn-select {
        background: var(--racine-black);
        color: white;
    }
    
    .btn-select:hover {
        background: var(--racine-orange);
        color: white;
    }
    
    .btn-current {
        background: #f0f0f0;
        color: #888;
        cursor: default;
    }
    
    .badge-current {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--racine-orange);
        color: white;
        padding: 0.25rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="pricing-container">
    <div class="pricing-header">
        <h2>Choisissez le plan adapté à vos ambitions</h2>
        <p>Changez de plan à tout moment pour débloquer plus de fonctionnalités</p>
    </div>

    <div class="plans-grid">
        @foreach($plans as $plan)
            @php
                $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
            @endphp
            
            <div class="plan-card {{ $isCurrent ? 'current' : '' }}">
                @if($isCurrent)
                    <div class="badge-current">Plan Actuel</div>
                @endif
                
                <div class="plan-name">{{ $plan->name }}</div>
                <div class="plan-price">
                    {{ number_format($plan->price, 0, ',', ' ') }} XAF
                    <span>/ mois</span>
                </div>
                
                <div class="plan-features">
                    @if($plan->description)
                        <p class="mb-4 text-muted">{{ $plan->description }}</p>
                    @endif
                    
                    {{-- Ici vous pourriez lister les capabilities si elles sont chargées --}}
                </div>
                
                @if($isCurrent)
                    <button class="btn-plan btn-current" disabled>Actif</button>
                @else
                    <form action="{{ route('creator.subscription.select', $plan) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-plan btn-select">
                            {{ $plan->price > 0 ? 'Choisir ce plan' : 'Activer gratuitement' }}
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
