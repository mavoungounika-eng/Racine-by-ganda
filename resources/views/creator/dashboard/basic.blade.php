@extends('layouts.creator')

@section('title', 'Tableau de Bord - RACINE BY GANDA')
@section('page-title', 'Tableau de bord')

@push('styles')
<style>
    .creator-hero {
        background: linear-gradient(135deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
        padding: 2rem 0;
        margin: -2rem -2rem 2rem -2rem;
        border-bottom: 2px solid rgba(237, 95, 30, 0.3);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .stat-card-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--racine-black);
        margin: 0.5rem 0;
    }
    
    .stat-card-title {
        font-size: 0.875rem;
        color: #8B7355;
        text-transform: uppercase;
    }
    
    .upgrade-banner {
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        padding: 1.5rem;
        border-radius: var(--radius-lg);
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .upgrade-banner h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
    }
    
    .upgrade-banner p {
        margin: 0 0 1rem 0;
        opacity: 0.9;
    }
    
    .upgrade-btn {
        display: inline-block;
        background: white;
        color: var(--racine-orange);
        padding: 0.75rem 1.5rem;
        border-radius: var(--radius-md);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition-fast);
    }
    
    .upgrade-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        color: var(--racine-orange);
        text-decoration: none;
    }
</style>
@endpush

@section('content')
<div class="creator-dashboard">
    
    {{-- HERO --}}
    <div class="creator-hero">
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%); display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-size: 2rem; font-weight: 700;">{{ strtoupper(substr($creatorProfile->brand_name ?? $user->name ?? 'C', 0, 1)) }}</span>
            </div>
            <div>
                <h2 style="color: white; margin: 0 0 0.5rem 0; font-size: 1.5rem;">Bonjour, {{ $creatorProfile->brand_name ?? $user->name ?? 'Créateur' }}</h2>
                <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.9rem;">Vue d'ensemble de votre activité</p>
            </div>
        </div>
    </div>

    {{-- UPGRADE BANNER --}}
    @if($user->activePlan() && $user->activePlan()->code === 'free')
    <div class="upgrade-banner">
        <h3>🚀 Passez au plan Officiel</h3>
        <p>Débloquez des fonctionnalités avancées : produits illimités, statistiques détaillées, et bien plus encore !</p>
        <a href="{{ route('creator.subscription.upgrade') }}" class="upgrade-btn">Découvrir les plans</a>
    </div>
    @endif

    {{-- ✅ C3: SCORE WIDGET (nouveau) --}}
    @if($creatorProfile && $creatorProfile->overall_score !== null)
    <div class="creator-card mb-6" style="background: linear-gradient(135deg, rgba(237, 95, 30, 0.05) 0%, rgba(255, 184, 0, 0.05) 100%); border: 2px solid rgba(237, 95, 30, 0.2);">
        <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1rem;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);">
                <i class="fas fa-star" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="flex: 1;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #2C1810; font-weight: 700;">
                    <i class="fas fa-chart-line text-[#ED5F1E] mr-2"></i>
                    Votre Score Créateur
                </h3>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2rem; font-weight: 700; color: #ED5F1E;">
                        {{ number_format($creatorProfile->overall_score, 0) }}<span style="font-size: 1rem; color: #8B7355;">/100</span>
                    </div>
                    <div style="flex: 1;">
                        <div style="height: 8px; background: #E5DDD3; border-radius: 10px; overflow: hidden;">
                            <div style="width: {{ $creatorProfile->overall_score }}%; height: 100%; background: linear-gradient(90deg, #ED5F1E, #FFB800); border-radius: 10px; transition: width 0.3s;"></div>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #8B7355;">
                            @if($creatorProfile->overall_score >= 80)
                                <i class="fas fa-check-circle text-green-600"></i> Excellent ! Votre profil est très attractif
                            @elseif($creatorProfile->overall_score >= 50)
                                <i class="fas fa-info-circle text-yellow-600"></i> Bon score, continuez à améliorer votre profil
                            @else
                                <i class="fas fa-exclamation-circle text-orange-600"></i> Complétez votre profil pour augmenter votre visibilité
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div style="background: white; padding: 1rem; border-radius: 12px; margin-top: 1rem;">
            <p style="margin: 0; font-size: 0.9rem; color: #2C1810;">
                <strong>💡 Pourquoi c'est important ?</strong> Votre score influence votre visibilité sur la marketplace. 
                Un score élevé = plus de clients potentiels.
            </p>
        </div>
    </div>
    @endif

    {{-- ONBOARDING WIDGET (V1.5) --}}
    @php
        $hasLogo = !empty($creatorProfile->logo_path);
        $hasProduct = ($stats['products_count'] ?? 0) > 0;
        $hasPayout = !empty($creatorProfile->payout_method);
        $progress = 0;
        if($hasLogo) $progress += 33;
        if($hasProduct) $progress += 34;
        if($hasPayout) $progress += 33;
    @endphp

    @if($progress < 100)
    <div style="background: white; border-radius: var(--radius-xl); padding: 1.5rem; box-shadow: var(--shadow-md); margin-bottom: 2rem; border: 1px solid #E5DDD3;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #2C1810;"><i class="fas fa-tasks text-[#ED5F1E] mr-2"></i> Complétez votre boutique</h3>
            <span style="background: #F8F6F3; color: #ED5F1E; padding: 2px 10px; border-radius: 99px; font-weight: 700; font-size: 0.8rem;">{{ $progress }}%</span>
        </div>
        
        <div style="width: 100%; height: 6px; background: #E5DDD3; border-radius: 10px; margin-bottom: 1.5rem; overflow: hidden;">
            <div style="width: {{ $progress }}%; height: 100%; background: linear-gradient(90deg, #ED5F1E, #FFB800); border-radius: 10px;"></div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            {{-- Step 1: Logo --}}
            <a href="{{ route('creator.settings.shop') }}" style="text-decoration: none;">
                <div style="padding: 1rem; border-radius: 12px; background: {{ $hasLogo ? '#F0FDF4' : 'white' }}; border: 1px solid {{ $hasLogo ? '#DCFCE7' : '#E5DDD3' }}; display: flex; align-items: center; gap: 1rem; transition: all 0.2s;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $hasLogo ? '#22C55E' : '#FED7AA' }}; color: {{ $hasLogo ? 'white' : '#9A3412' }}; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                        <i class="fas {{ $hasLogo ? 'fa-check' : 'fa-camera' }}"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: {{ $hasLogo ? '#166534' : '#2C1810' }}; font-size: 0.9rem;">Ajouter un logo</div>
                        <div style="font-size: 0.75rem; color: gray;">{{ $hasLogo ? 'Fait' : 'Pour votre identité' }}</div>
                    </div>
                </div>
            </a>
            
            {{-- Step 2: Product --}}
            <a href="{{ route('creator.products.create') }}" style="text-decoration: none;">
                <div style="padding: 1rem; border-radius: 12px; background: {{ $hasProduct ? '#F0FDF4' : 'white' }}; border: 1px solid {{ $hasProduct ? '#DCFCE7' : '#E5DDD3' }}; display: flex; align-items: center; gap: 1rem; transition: all 0.2s;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $hasProduct ? '#22C55E' : '#FED7AA' }}; color: {{ $hasProduct ? 'white' : '#9A3412' }}; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                        <i class="fas {{ $hasProduct ? 'fa-check' : 'fa-plus' }}"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: {{ $hasProduct ? '#166534' : '#2C1810' }}; font-size: 0.9rem;">Créer un produit</div>
                        <div style="font-size: 0.75rem; color: gray;">{{ $hasProduct ? 'Fait' : 'Votre premier article' }}</div>
                    </div>
                </div>
            </a>

            {{-- Step 3: Payout --}}
            <a href="{{ route('creator.settings.payment') }}" style="text-decoration: none;">
                <div style="padding: 1rem; border-radius: 12px; background: {{ $hasPayout ? '#F0FDF4' : 'white' }}; border: 1px solid {{ $hasPayout ? '#DCFCE7' : '#E5DDD3' }}; display: flex; align-items: center; gap: 1rem; transition: all 0.2s;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $hasPayout ? '#22C55E' : '#FED7AA' }}; color: {{ $hasPayout ? 'white' : '#9A3412' }}; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                        <i class="fas {{ $hasPayout ? 'fa-check' : 'fa-wallet' }}"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: {{ $hasPayout ? '#166534' : '#2C1810' }}; font-size: 0.9rem;">Mode de versement</div>
                        <div style="font-size: 0.75rem; color: gray;">{{ $hasPayout ? 'Fait' : 'Pour recevoir vos gains' }}</div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    @endif

    {{-- STATS BASIC --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-title">Produits</div>
            <div class="stat-card-value">{{ $stats['products_count'] ?? 0 }}</div>
            <div style="font-size: 0.875rem; color: #8B7355;">{{ $stats['active_products_count'] ?? 0 }} actifs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-title">Ventes Total</div>
            <div class="stat-card-value" style="font-size: 1.5rem;">
                {{ number_format($stats['total_sales'] ?? 0, 0, ',', ' ') }}<small style="font-size: 0.6em;"> FCFA</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-title">Commandes en Attente</div>
            <div class="stat-card-value">{{ $stats['pending_orders'] ?? 0 }}</div>
        </div>
    </div>

    {{-- QUICK ACTIONS --}}
    <div style="background: white; border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md);">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: var(--racine-black);">Actions Rapides</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="{{ route('creator.products.index') }}" style="padding: 1rem; background: #F8F6F3; border-radius: var(--radius-md); text-decoration: none; color: var(--racine-black); text-align: center; transition: var(--transition-fast);">
                <i class="fas fa-box" style="font-size: 1.5rem; color: var(--racine-orange); margin-bottom: 0.5rem; display: block;"></i>
                <strong>Mes Produits</strong>
            </a>
            <a href="{{ route('creator.orders.index') }}" style="padding: 1rem; background: #F8F6F3; border-radius: var(--radius-md); text-decoration: none; color: var(--racine-black); text-align: center; transition: var(--transition-fast);">
                <i class="fas fa-shopping-bag" style="font-size: 1.5rem; color: var(--racine-orange); margin-bottom: 0.5rem; display: block;"></i>
                <strong>Mes Commandes</strong>
            </a>
            @if($user->hasCapability('can_view_advanced_stats'))
            <a href="{{ route('creator.stats.index') }}" style="padding: 1rem; background: #F8F6F3; border-radius: var(--radius-md); text-decoration: none; color: var(--racine-black); text-align: center; transition: var(--transition-fast);">
                <i class="fas fa-chart-line" style="font-size: 1.5rem; color: var(--racine-orange); margin-bottom: 0.5rem; display: block;"></i>
                <strong>Statistiques</strong>
            </a>
            @else
            <div style="padding: 1rem; background: #F8F6F3; border-radius: var(--radius-md); text-align: center; opacity: 0.5; position: relative;">
                <i class="fas fa-lock" style="font-size: 1.5rem; color: #8B7355; margin-bottom: 0.5rem; display: block;"></i>
                <strong style="color: #8B7355;">Statistiques</strong>
                <small style="display: block; margin-top: 0.25rem; font-size: 0.75rem; color: #8B7355;">Plan Officiel requis</small>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

