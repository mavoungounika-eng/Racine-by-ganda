@extends('layouts.creator')

@section('title', 'Choisir un Plan - RACINE BY GANDA')
@section('page-title', 'Plans & Abonnements')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator-premium.css') }}">
<style>
    .comparison-table {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        margin-top: 3rem;
    }
    
    .comparison-table th {
        background: linear-gradient(135deg, #2C1810 0%, #3D2418 100%);
        color: white;
        padding: 1.5rem;
        font-weight: 600;
        text-align: center;
    }
    
    .comparison-table td {
        padding: 1.25rem;
        text-align: center;
        border-bottom: 1px solid #E5DDD3;
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
    
    .plan-highlight {
        background: linear-gradient(135deg, rgba(237, 95, 30, 0.1) 0%, rgba(255, 184, 0, 0.1) 100%);
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    
    {{-- Feedback Messages --}}
    @if(session('success'))
        <div class="alert-box alert-success mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-box alert-error mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-[#2C1810] mb-4" style="font-family: 'Libre Baskerville', serif;">
            💎 Choisissez Votre Plan d'Abonnement
        </h1>
        <p class="text-lg text-[#8B7355] max-w-3xl mx-auto">
            Sélectionnez le plan qui correspond à vos ambitions. Tous les plans incluent l'accès à la plateforme, 
            le paiement sécurisé et le support client.
        </p>
        
        {{-- Current Plan Badge --}}
        @if($currentSubscription && $currentSubscription->plan)
            <div class="mt-4 inline-block px-4 py-2 bg-green-100 border-2 border-green-300 rounded-xl">
                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                <span class="font-semibold text-green-800">Plan actuel : {{ $currentSubscription->plan->name }}</span>
            </div>
        @endif
    </div>

    {{-- Plans Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        @foreach($plans as $plan)
            <div class="premium-card {{ $plan->code === 'premium' ? 'plan-highlight' : '' }}" style="position: relative;">
                {{-- Badge --}}
                <div class="mb-4">
                    @if($currentSubscription && $currentSubscription->plan && $currentSubscription->plan->id === $plan->id)
                        <span class="status-badge status-active">Plan Actuel</span>
                    @elseif($plan->code === 'premium')
                        <span class="status-badge" style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color: white;">⭐ Recommandé</span>
                    @endif
                </div>

                <h3 class="text-2xl font-bold text-[#2C1810] mb-2">{{ $plan->name }}</h3>
                <p class="text-[#8B7355] mb-6">{{ $plan->description }}</p>

                <div class="mb-6">
                    <span class="stat-value" style="font-size: 3rem;">{{ number_format($plan->price, 0, ',', ' ') }}</span>
                    <span class="text-xl text-[#8B7355]"> FCFA/mois</span>
                </div>

                {{-- Key Features --}}
                <ul class="space-y-3 mb-8">
                    @if($plan->features)
                        @foreach(array_slice($plan->features, 0, 5) as $feature)
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                <span class="text-[#2C1810]">{{ $feature }}</span>
                            </li>
                        @endforeach
                    @endif
                </ul>

                {{-- CTA Button --}}
                @if($currentSubscription && $currentSubscription->plan && $currentSubscription->plan->id === $plan->id)
                    <button disabled class="premium-btn" style="background: #10B981;">
                        <i class="fas fa-check mr-2"></i>
                        Votre Plan Actuel
                    </button>
                @else
                    {{-- Payment Method Selection --}}
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-[#2C1810] mb-3">Choisissez votre mode de paiement :</p>
                        
                        {{-- Stripe Payment --}}
                        <a href="{{ route('creator.subscription.checkout', $plan) }}" 
                           class="block mb-3 p-3 border-2 border-[#E5DDD3] rounded-xl hover:border-[#ED5F1E] hover:bg-orange-50 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
                                    <i class="fab fa-stripe text-blue-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-[#2C1810]">Carte Bancaire</div>
                                    <div class="text-xs text-[#8B7355]">Paiement sécurisé via Stripe</div>
                                </div>
                                <i class="fas fa-arrow-right text-[#8B7355] group-hover:text-[#ED5F1E]"></i>
                            </div>
                        </a>
                        
                        {{-- Mobile Money Payment --}}
                        <a href="{{ route('creator.subscription.checkout.momo', $plan) }}" 
                           class="block p-3 border-2 border-[#E5DDD3] rounded-xl hover:border-[#ED5F1E] hover:bg-orange-50 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center group-hover:bg-orange-200">
                                    <i class="fas fa-mobile-alt text-orange-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-[#2C1810]">Mobile Money</div>
                                    <div class="text-xs text-[#8B7355]">Orange, MTN, Wave...</div>
                                </div>
                                <i class="fas fa-arrow-right text-[#8B7355] group-hover:text-[#ED5F1E]"></i>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Detailed Comparison Table --}}
    <div class="comparison-table">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-left">Fonctionnalités</th>
                    @foreach($plans as $plan)
                        <th class="{{ $plan->code === 'premium' ? 'plan-highlight' : '' }}">
                            {{ $plan->name }}
                            <div class="text-sm font-normal opacity-75 mt-1">
                                {{ number_format($plan->price, 0, ',', ' ') }} FCFA/mois
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Gestion Produits --}}
                <tr>
                    <td colspan="4" class="feature-category">
                        <i class="fas fa-box mr-2 text-[#ED5F1E]"></i>
                        Gestion des Produits
                    </td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Nombre de produits</td>
                    @foreach($plans as $plan)
                        @php
                            $capability = $plan->capabilities->where('capability_key', 'max_products')->first();
                            $maxProducts = $capability?->value['int'] ?? 0;
                        @endphp
                        <td class="{{ $plan->code === 'premium' ? 'plan-highlight' : '' }}">
                            @if($maxProducts === -1)
                                <strong>Illimité</strong>
                            @else
                                {{ $maxProducts }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                {{-- Photos par produit: capability non définie dans seeder, retirer de l'affichage --}}
                {{-- Cette ligne sera réactivée quand la capability sera ajoutée au seeder --}}
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Variantes produits</td>
                    <td><i class="fas fa-check text-green-600"></i></td>
                    <td><i class="fas fa-check text-green-600"></i></td>
                    <td class="plan-highlight"><i class="fas fa-check text-green-600"></i></td>
                </tr>

                {{-- Analytics & Stats --}}
                <tr>
                    <td colspan="4" class="feature-category">
                        <i class="fas fa-chart-line mr-2 text-[#ED5F1E]"></i>
                        Analytics & Statistiques
                    </td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Statistiques de base</td>
                    <td><i class="fas fa-check text-green-600"></i></td>
                    <td><i class="fas fa-check text-green-600"></i></td>
                    <td class="plan-highlight"><i class="fas fa-check text-green-600"></i></td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Analytics avancées</td>
                    @foreach($plans as $plan)
                        @php
                            $capability = $plan->capabilities->where('capability_key', 'can_view_analytics')->first();
                            $hasAnalytics = $capability?->value['bool'] ?? false;
                        @endphp
                        <td class="{{ $plan->code === 'premium' ? 'plan-highlight' : '' }}">
                            @if($hasAnalytics)
                                <i class="fas fa-check text-green-600"></i>
                            @else
                                <i class="fas fa-times text-red-400"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Rapports exportables</td>
                    @foreach($plans as $plan)
                        @php
                            $capability = $plan->capabilities->where('capability_key', 'can_export_data')->first();
                            $canExport = $capability?->value['bool'] ?? false;
                        @endphp
                        <td class="{{ $plan->code === 'premium' ? 'plan-highlight' : '' }}">
                            @if($canExport)
                                <i class="fas fa-check text-green-600"></i>
                            @else
                                <i class="fas fa-times text-red-400"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>

                {{-- Support & Services --}}
                <tr>
                    <td colspan="4" class="feature-category">
                        <i class="fas fa-headset mr-2 text-[#ED5F1E]"></i>
                        Support & Services
                    </td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Support email</td>
                    <td>48h</td>
                    <td>24h</td>
                    <td class="plan-highlight"><strong>12h prioritaire</strong></td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Chat en direct</td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td><i class="fas fa-check text-green-600"></i></td>
                    <td class="plan-highlight"><i class="fas fa-check text-green-600"></i></td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Gestionnaire de compte dédié</td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td class="plan-highlight"><i class="fas fa-check text-green-600"></i></td>
                </tr>

                {{-- Marketing & Promotion --}}
                <tr>
                    <td colspan="4" class="feature-category">
                        <i class="fas fa-bullhorn mr-2 text-[#ED5F1E]"></i>
                        Marketing & Promotion
                    </td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Mise en avant sur marketplace</td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td>1x/mois</td>
                    <td class="plan-highlight"><strong>3x/mois</strong></td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Codes promo personnalisés</td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td><i class="fas fa-check text-green-600"></i></td>
                    <td class="plan-highlight"><i class="fas fa-check text-green-600"></i></td>
                </tr>
                <tr>
                    <td class="text-left font-semibold text-[#2C1810]">Newsletter dédiée</td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td><i class="fas fa-times text-red-400"></i></td>
                    <td class="plan-highlight"><i class="fas fa-check text-green-600"></i></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- FAQ Section --}}
    <div class="mt-12 premium-card">
        <h3 class="text-2xl font-bold text-[#2C1810] mb-6">
            <i class="fas fa-question-circle text-[#ED5F1E] mr-2"></i>
            Questions Fréquentes
        </h3>
        
        <div class="space-y-4">
            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <h4 class="font-bold text-[#2C1810] mb-2">💳 Comment fonctionne le paiement ?</h4>
                <p class="text-[#8B7355]">
                    Le paiement est sécurisé via Stripe. Vous serez débité automatiquement chaque mois. 
                    Vous pouvez annuler à tout moment depuis vos paramètres.
                </p>
            </div>

            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <h4 class="font-bold text-[#2C1810] mb-2">🔄 Puis-je changer de plan ?</h4>
                <p class="text-[#8B7355]">
                    Oui ! Vous pouvez upgrader ou downgrader votre plan à tout moment. 
                    Les changements prennent effet immédiatement avec ajustement au prorata.
                </p>
            </div>

            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <h4 class="font-bold text-[#2C1810] mb-2">💰 Quels sont les frais de transaction ?</h4>
                <p class="text-[#8B7355]">
                    RACINE prélève 5% de frais de service + TVA 18% sur chaque vente. 
                    Vous recevez 100% du prix HT de vos produits.
                </p>
            </div>

            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <h4 class="font-bold text-[#2C1810] mb-2">❌ Puis-je annuler mon abonnement ?</h4>
                <p class="text-[#8B7355]">
                    Oui, sans engagement. Vous pouvez annuler à tout moment. 
                    Votre accès reste actif jusqu'à la fin de la période payée.
                </p>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-12 text-center">
        <a href="{{ route('creator.dashboard') }}" class="text-[#8B7355] hover:text-[#ED5F1E] transition-colors font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour au tableau de bord
        </a>
    </div>
</div>
@endsection
