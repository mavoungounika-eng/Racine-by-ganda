@extends('layouts.creator')

@section('title', 'Choisir un Plan - RACINE BY GANDA')
@section('page-title', 'Plans & Abonnements')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator-premium.css') }}">
<style>
    .comparison-table-wrapper {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        margin-top: 3rem;
        border: 1px solid rgba(212, 165, 116, 0.1);
    }
    
    .comparison-table th {
        background: linear-gradient(135deg, #2C1810 0%, #3D2418 100%);
        color: white;
        padding: 1.5rem;
        font-weight: 600;
        text-align: center;
        border: none;
    }
    
    .comparison-table td {
        padding: 1.25rem;
        text-align: center;
        border-bottom: 1px solid #E5DDD3;
        vertical-align: middle;
    }
    
    .comparison-table tr:last-child td {
        border-bottom: none;
    }
    
    .comparison-table tr:hover {
        background: #F8F6F3;
    }
    
    .feature-category {
        background: #F8F6F3;
        font-weight: 700;
        color: #2C1810;
        text-align: left !important;
        padding: 1rem 1.5rem !important;
    }
    
    .plan-highlight-bg {
        background: linear-gradient(135deg, rgba(237, 95, 30, 0.05) 0%, rgba(255, 184, 0, 0.05) 100%);
    }

    .plan-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .payment-option-link {
        display: block;
        padding: 1rem;
        border: 2px solid #E5DDD3;
        border-radius: 16px;
        transition: all 0.3s ease;
        text-decoration: none !important;
        margin-bottom: 1rem;
    }

    .payment-option-link:hover {
        border-color: var(--racine-orange);
        background: var(--racine-orange-ultra-light);
        transform: translateY(-2px);
    }

    .payment-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            
            {{-- Navigation --}}
            @include('creator.partials.settings-nav')

            {{-- Feedback Messages --}}
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 rounded-xl">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-xl">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="text-center mb-5">
                <h1 class="display-4 font-weight-bold text-dark mb-3" style="font-family: 'Libre Baskerville', serif;">
                    💎 Choisissez Votre Plan d'Abonnement
                </h1>
                <p class="lead text-muted mx-auto" style="max-width: 800px;">
                    Sélectionnez le plan qui correspond à vos ambitions. Tous les plans incluent l'accès à la plateforme, 
                    le paiement sécurisé et le support client.
                </p>
                
                {{-- Current Plan Badge --}}
                @if($currentSubscription && $currentSubscription->plan)
                    <div class="mt-4 d-inline-block px-4 py-2 bg-success text-white rounded-pill shadow-sm">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="font-weight-bold">Plan actuel : {{ $currentSubscription->plan->name }}</span>
                    </div>
                @endif
            </div>

            {{-- Plans Cards --}}
            <div class="row">
                @foreach($plans as $plan)
                    <div class="col-md-4 mb-4">
                        <div class="creator-card plan-card {{ $plan->code === 'premium' ? 'border-primary' : '' }}">
                            {{-- Badge --}}
                            <div class="mb-3">
                                @if($currentSubscription && $currentSubscription->plan && $currentSubscription->plan->id === $plan->id)
                                    <span class="badge badge-success px-3 py-2 rounded-pill">Plan Actuel</span>
                                @elseif($plan->code === 'premium')
                                    <span class="badge px-3 py-2 rounded-pill text-white" style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);">⭐ Recommandé</span>
                                @else
                                    <div style="height: 28px;"></div>
                                @endif
                            </div>

                            <h3 class="h2 font-weight-bold text-dark mb-2">{{ $plan->name }}</h3>
                            <p class="text-muted mb-4">{{ $plan->description }}</p>

                            <div class="mb-4">
                                <span class="display-4 font-weight-bold text-dark">{{ number_format($plan->price, 0, ',', ' ') }}</span>
                                <span class="h4 text-muted"> FCFA/mois</span>
                            </div>

                            {{-- Key Features --}}
                            <ul class="list-unstyled mb-auto">
                                @if($plan->features)
                                    @foreach(array_slice($plan->features, 0, 5) as $feature)
                                        <li class="d-flex align-items-start mb-3">
                                            <i class="fas fa-check-circle text-success mt-1 mr-2"></i>
                                            <span class="text-dark">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>

                            <hr class="my-4">

                            {{-- CTA Section --}}
                            @if($currentSubscription && $currentSubscription->plan && $currentSubscription->plan->id === $plan->id)
                                <button disabled class="btn btn-success btn-lg btn-block rounded-pill py-3">
                                    <i class="fas fa-check mr-2"></i>
                                    Votre Plan Actuel
                                </button>
                            @else
                                <div class="payment-options">
                                    <p class="small font-weight-bold text-dark mb-3">Choisissez votre mode de paiement :</p>
                                    
                                    {{-- Stripe Payment --}}
                                    <a href="{{ route('creator.subscription.checkout', $plan) }}" class="payment-option-link">
                                        <div class="d-flex align-items-center">
                                            <div class="payment-icon-box bg-primary text-white">
                                                <i class="fab fa-stripe fa-2x"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="font-weight-bold text-dark">Carte Bancaire</div>
                                                <div class="small text-muted">Stripe Sécurisé</div>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </a>
                                    
                                    {{-- Mobile Money Payment --}}
                                    <a href="{{ route('creator.subscription.checkout.momo', $plan) }}" class="payment-option-link">
                                        <div class="d-flex align-items-center">
                                            <div class="payment-icon-box bg-warning text-white">
                                                <i class="fas fa-mobile-alt fa-lg"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="font-weight-bold text-dark">Mobile Money</div>
                                                <div class="small text-muted">Orange, MTN, Wave...</div>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Detailed Comparison Table --}}
            <div class="comparison-table-wrapper mb-5">
                <div class="table-responsive">
                    <table class="table comparison-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-left">Fonctionnalités</th>
                                @foreach($plans as $plan)
                                    <th class="{{ $plan->code === 'premium' ? 'plan-highlight-bg' : '' }}">
                                        {{ $plan->name }}
                                        <div class="small font-weight-normal opacity-75 mt-1">
                                            {{ number_format($plan->price, 0, ',', ' ') }} FCFA/mois
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Gestion Produits --}}
                            <tr>
                                <td colspan="{{ count($plans) + 1 }}" class="feature-category">
                                    <i class="fas fa-box mr-2 text-warning"></i>
                                    Gestion des Produits
                                </td>
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Nombre de produits</td>
                                @foreach($plans as $plan)
                                    @php
                                        $capability = $plan->capabilities->where('capability_key', 'max_products')->first();
                                        $maxProducts = $capability?->value['int'] ?? 0;
                                    @endphp
                                    <td class="{{ $plan->code === 'premium' ? 'plan-highlight-bg' : '' }}">
                                        @if($maxProducts === -1)
                                            <span class="badge badge-dark">Illimité</span>
                                        @else
                                            {{ $maxProducts }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Variantes produits</td>
                                @foreach($plans as $plan)
                                    <td class="{{ $plan->code === 'premium' ? 'plan-highlight-bg' : '' }}">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Analytics & Stats --}}
                            <tr>
                                <td colspan="{{ count($plans) + 1 }}" class="feature-category">
                                    <i class="fas fa-chart-line mr-2 text-warning"></i>
                                    Analytics & Statistiques
                                </td>
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Statistiques de base</td>
                                @foreach($plans as $plan)
                                    <td class="{{ $plan->code === 'premium' ? 'plan-highlight-bg' : '' }}">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Analytics avancées</td>
                                @foreach($plans as $plan)
                                    @php
                                        $capability = $plan->capabilities->where('capability_key', 'can_view_analytics')->first();
                                        $hasAnalytics = $capability?->value['bool'] ?? false;
                                    @endphp
                                    <td class="{{ $plan->code === 'premium' ? 'plan-highlight-bg' : '' }}">
                                        @if($hasAnalytics)
                                            <i class="fas fa-check text-success"></i>
                                        @else
                                            <i class="fas fa-times text-danger"></i>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Support & Services --}}
                            <tr>
                                <td colspan="{{ count($plans) + 1 }}" class="feature-category">
                                    <i class="fas fa-headset mr-2 text-warning"></i>
                                    Support & Services
                                </td>
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Support email</td>
                                <td>48h</td>
                                <td>24h</td>
                                <td class="plan-highlight-bg text-dark font-weight-bold">12h prioritaire</td>
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Chat en direct</td>
                                <td><i class="fas fa-times text-danger"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td class="plan-highlight-bg"><i class="fas fa-check text-success"></i></td>
                            </tr>

                            {{-- Marketing --}}
                            <tr>
                                <td colspan="{{ count($plans) + 1 }}" class="feature-category">
                                    <i class="fas fa-bullhorn mr-2 text-warning"></i>
                                    Marketing & Promotion
                                </td>
                            </tr>
                            <tr>
                                <td class="text-left font-weight-bold text-dark">Mise en avant sur marketplace</td>
                                <td><i class="fas fa-times text-danger"></i></td>
                                <td>1x/mois</td>
                                <td class="plan-highlight-bg text-dark font-weight-bold">3x/mois</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FAQ Section --}}
            <div class="creator-card mb-5">
                <h3 class="h3 font-weight-bold text-dark mb-4">
                    <i class="fas fa-question-circle text-warning mr-2"></i>
                    Questions Fréquentes
                </h3>
                
                <div class="accordion" id="faqAccordion">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="p-4 bg-light rounded-xl h-100">
                                <h4 class="h6 font-weight-bold text-dark mb-2">💳 Comment fonctionne le paiement ?</h4>
                                <p class="small text-muted mb-0">
                                    Le paiement est sécurisé via Stripe. Vous serez débité automatiquement chaque mois. 
                                    Vous pouvez annuler à tout moment depuis vos paramètres.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 bg-light rounded-xl h-100">
                                <h4 class="h6 font-weight-bold text-dark mb-2">🔄 Puis-je changer de plan ?</h4>
                                <p class="small text-muted mb-0">
                                    Oui ! Vous pouvez upgrader ou downgrader votre plan à tout moment. 
                                    Les changements prennent effet immédiatement avec ajustement au prorata.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 bg-light rounded-xl h-100">
                                <h4 class="h6 font-weight-bold text-dark mb-2">💰 Quels sont les frais de transaction ?</h4>
                                <p class="small text-muted mb-0">
                                    RACINE prélève 5% de frais de service + TVA 18% sur chaque vente. 
                                    Vous recevez 100% du prix HT de vos produits.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 bg-light rounded-xl h-100">
                                <h4 class="h6 font-weight-bold text-dark mb-2">❌ Puis-je annuler mon abonnement ?</h4>
                                <p class="small text-muted mb-0">
                                    Oui, sans engagement. Vous pouvez annuler à tout moment. 
                                    Votre accès reste actif jusqu'à la fin de la période payée.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Back Button --}}
            <div class="text-center mb-5">
                <a href="{{ route('creator.dashboard') }}" class="btn btn-link text-muted font-weight-bold text-decoration-none">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

