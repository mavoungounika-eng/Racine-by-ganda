@extends('layouts.frontend')

@section('title', 'Devenir Créateur - RACINE BY GANDA')

@push('styles')
<style>
    /* ===== HERO SECTION ===== */
    .become-creator-hero {
        background: linear-gradient(135deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
        padding: 6rem 0 4rem;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    
    .become-creator-hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4A574' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .hero-title {
        font-family: var(--font-heading);
        font-size: 3.5rem;
        font-weight: 400;
        color: white;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }
    
    .hero-subtitle {
        font-size: 1.25rem;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 3rem;
        line-height: 1.6;
    }
    
    .hero-cta {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-primary-hero {
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        padding: 1rem 2.5rem;
        border-radius: var(--radius-lg);
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        transition: var(--transition-fast);
        box-shadow: var(--shadow-orange);
        border: none;
    }
    
    .btn-primary-hero:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary-hero {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        padding: 1rem 2.5rem;
        border-radius: var(--radius-lg);
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: var(--transition-fast);
    }
    
    .btn-secondary-hero:hover {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        text-decoration: none;
    }
    
    /* ===== PLANS SECTION ===== */
    .plans-section {
        padding: 5rem 0;
        background: #F8F6F3;
    }
    
    .plans-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }
    
    .plan-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        position: relative;
        transition: var(--transition-fast);
        border: 2px solid transparent;
    }
    
    .plan-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
    }
    
    .plan-card.recommended {
        border-color: var(--racine-orange);
        box-shadow: 0 8px 32px rgba(237, 95, 30, 0.2);
    }
    
    .plan-card.recommended::before {
        content: '⭐ RECOMMANDÉ';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
    }
    
    .plan-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid #F8F6F3;
    }
    
    .plan-name {
        font-family: var(--font-heading);
        font-size: 1.75rem;
        font-weight: 400;
        color: var(--racine-black);
        margin-bottom: 0.5rem;
    }
    
    .plan-price {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--racine-orange);
        margin-bottom: 0.25rem;
    }
    
    .plan-price-subtitle {
        font-size: 0.875rem;
        color: #8B7355;
    }
    
    .plan-description {
        text-align: center;
        color: #8B7355;
        font-size: 0.95rem;
        margin-bottom: 2rem;
        line-height: 1.6;
    }
    
    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 2rem 0;
    }
    
    .plan-features li {
        padding: 0.75rem 0;
        display: flex;
        align-items: start;
        gap: 0.75rem;
        color: var(--racine-black);
    }
    
    .plan-features li i {
        color: var(--racine-orange);
        margin-top: 0.25rem;
        flex-shrink: 0;
    }
    
    .plan-cta {
        width: 100%;
        padding: 1rem;
        border-radius: var(--radius-lg);
        text-decoration: none;
        font-weight: 600;
        text-align: center;
        display: block;
        transition: var(--transition-fast);
        border: 2px solid transparent;
    }
    
    .plan-cta.primary {
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        box-shadow: var(--shadow-orange);
    }
    
    .plan-cta.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .plan-cta.secondary {
        background: white;
        color: var(--racine-orange);
        border-color: var(--racine-orange);
    }
    
    .plan-cta.secondary:hover {
        background: var(--racine-orange);
        color: white;
        text-decoration: none;
    }
    
    .plan-cta.free {
        background: #F8F6F3;
        color: var(--racine-black);
        border-color: #E5DDD3;
    }
    
    .plan-cta.free:hover {
        background: #E5DDD3;
        color: var(--racine-black);
        text-decoration: none;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
        }
        
        .plans-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="become-creator-page">
    {{-- HERO SECTION --}}
    <section class="become-creator-hero">
        <div class="hero-content">
            <h1 class="hero-title">Transformez votre talent en marque rentable.</h1>
            <p class="hero-subtitle">
                RACINE BY GANDA accompagne les créateurs sérieux avec des outils professionnels, 
                une visibilité réelle et des paiements sécurisés.
            </p>
            <div class="hero-cta">
                <a href="{{ route('creator.register') }}" class="btn-primary-hero">
                    Devenir créateur officiel
                </a>
                <a href="#plans" class="btn-secondary-hero">
                    Découvrir les plans
                </a>
            </div>
        </div>
    </section>

    {{-- PLANS SECTION --}}
    <section class="plans-section" id="plans">
        <div class="plans-container">
            <div class="plans-grid">
                @foreach($plans as $plan)
                    @php
                        $isFree = $plan->code === 'free';
                        $isOfficial = $plan->code === 'official';
                        $isPremium = $plan->code === 'premium';
                        
                        // Features selon le plan
                        $features = [];
                        if ($isFree) {
                            $features = [
                                'Jusqu\'à 5 produits',
                                'Commission élevée',
                                'Dashboard basique',
                                'Pas de mise en avant',
                                'Paiements soumis à validation',
                            ];
                        } elseif ($isOfficial) {
                            $features = [
                                'Produits illimités',
                                'Commission réduite',
                                'Boutique personnalisée',
                                'Statistiques complètes',
                                'Badge Créateur Officiel',
                                'Paiements sécurisés et réguliers',
                            ];
                        } elseif ($isPremium) {
                            $features = [
                                'Mise en avant sur la marketplace',
                                'Dashboard premium',
                                'Accès ventes physiques',
                                'Exports & analytics avancés',
                                'Support prioritaire',
                                'Commission minimale',
                            ];
                        }
                    @endphp
                    
                    <div class="plan-card {{ $isOfficial ? 'recommended' : '' }}">
                        <div class="plan-header">
                            <h2 class="plan-name">
                                @if($isFree) 🟢 CRÉATEUR DÉCOUVERTE
                                @elseif($isOfficial) 🔵 CRÉATEUR OFFICIEL
                                @else 🟣 CRÉATEUR PREMIUM
                                @endif
                            </h2>
                            @if($isFree)
                                <div class="plan-price">Gratuit</div>
                            @else
                                <div class="plan-price">{{ number_format($plan->price, 0, ',', ' ') }} XAF</div>
                                <div class="plan-price-subtitle">/ mois</div>
                            @endif
                        </div>
                        
                        <p class="plan-description">
                            @if($isFree)
                                Tester la plateforme, publier vos premiers produits.
                            @elseif($isOfficial)
                                Le statut minimum pour vendre sérieusement sur RACINE.
                            @else
                                Pour les marques ambitieuses et partenaires stratégiques.
                            @endif
                        </p>
                        
                        <ul class="plan-features">
                            @foreach($features as $feature)
                                <li>
                                    <i class="fas fa-{{ $isFree ? 'check' : 'check-circle' }}"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                        
                        <a href="{{ $isFree ? route('creator.register') : route('creator.subscription.select', $plan) }}" 
                           class="plan-cta {{ $isOfficial ? 'primary' : ($isFree ? 'free' : 'secondary') }}">
                            @if($isFree)
                                Commencer gratuitement
                            @elseif($isOfficial)
                                Passer créateur officiel
                            @else
                                Accéder au Premium
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection

