@extends('layouts.frontend')

@section('title', 'Devenir Créateur - RACINE BY GANDA')

@push('styles')
<style nonce="{{ csp_nonce() }}">
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
        background: rgba(22,13,12,0.05);
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
        border-bottom: 2px solid rgba(22,13,12,0.05);
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
        color: rgba(22,13,12,0.5);
    }
    
    .plan-description {
        text-align: center;
        color: rgba(22,13,12,0.5);
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
        background: rgba(22,13,12,0.05);
        color: var(--racine-black);
        border-color: rgba(22,13,12,0.1);
    }
    
    .plan-cta.free:hover {
        background: rgba(22,13,12,0.1);
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
                @forelse($plans->whereIn('code', ['atelier','maison','signature']) as $plan)
                    @php
                        $isAtelier   = $plan->code === 'atelier';
                        $isMaison    = $plan->code === 'maison';
                        $isSignature = $plan->code === 'signature';

                        $featuresMap = [
                            'atelier' => [
                                'Jusqu\'à 80 produits',
                                'Dashboard créateur basique',
                                'Gestion des commandes',
                                '30 jours d\'essai gratuit',
                            ],
                            'maison' => [
                                'Jusqu\'à 250 produits',
                                'Dashboard avancé + analytics',
                                'Export des données',
                                'Add-on POS +8 000 FCFA/mois',
                                '30 jours d\'essai gratuit',
                            ],
                            'signature' => [
                                'Produits illimités',
                                'POS Electron inclus',
                                'Analytics avancées',
                                'Support dédié',
                                '30 jours d\'essai gratuit',
                            ],
                        ];
                        $features = $featuresMap[$plan->code] ?? $plan->features ?? [];
                    @endphp

                    <div class="plan-card {{ $isMaison ? 'recommended' : '' }}">
                        <div class="plan-header">
                            <h2 class="plan-name">
                                @if($isAtelier) ATELIER
                                @elseif($isMaison) MAISON
                                @else SIGNATURE
                                @endif
                            </h2>
                            <div class="plan-price">{{ number_format($plan->price, 0, ',', ' ') }} FCFA</div>
                            <div class="plan-price-subtitle">/ mois</div>
                            @if($plan->quarterly_price)
                            <div style="font-size:0.78rem;color:rgba(255,255,255,0.7);margin-top:0.25rem;">
                                ou {{ number_format($plan->quarterly_price, 0, ',', ' ') }} FCFA / trimestre
                                · {{ number_format($plan->annual_price, 0, ',', ' ') }} FCFA / an
                            </div>
                            @endif
                        </div>

                        <p class="plan-description">
                            @if($isAtelier) Démarrez votre activité de créateur en toute sérénité.
                            @elseif($isMaison) Développez votre marque avec des outils avancés.
                            @else Pour les créateurs qui veulent aller jusqu'au bout.
                            @endif
                        </p>

                        @if($plan->trial_days)
                        <div style="text-align:center;margin-bottom:0.75rem;">
                            <span style="display:inline-block;padding:0.25rem 0.75rem;border-radius:999px;background:rgba(34,197,94,0.15);color:#15803d;font-size:0.8rem;font-weight:600;">{{ $plan->trial_days }} jours gratuits</span>
                        </div>
                        @endif

                        <ul class="plan-features">
                            @foreach($features as $feature)
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if($isSignature)
                            <a href="{{ route('frontend.contact') }}" class="plan-cta secondary">
                                Nous contacter
                            </a>
                        @else
                            <a href="{{ route('creator.register') }}" class="plan-cta {{ $isMaison ? 'primary' : 'free' }}">
                                Commencer — {{ $plan->name }}
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="plan-card" style="grid-column: 1/-1; text-align:center; padding: 3rem;">
                        <p style="color:#8B7355; font-size:1.1rem;">Les plans seront disponibles prochainement. <a href="{{ route('frontend.contact') }}" style="color:#ED5F1E;">Contactez-nous</a></p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection

