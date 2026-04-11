@extends('layouts.creator')

@section('title', 'Abonnement Activé - RACINE BY GANDA')
@section('page-title', 'Félicitations !')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator-premium.css') }}">
<style>
    .success-animation {
        animation: scaleIn 0.5s ease-out;
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0.8);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    .feature-unlock {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        border: 2px solid #E5DDD3;
        transition: all 0.3s;
    }
    
    .feature-unlock:hover {
        border-color: #10B981;
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.15);
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    
    {{-- Success Hero --}}
    <div class="text-center mb-12 success-animation">
        <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
            <i class="fas fa-check text-6xl text-white"></i>
        </div>
        
        <h1 class="text-4xl font-bold text-[#2C1810] mb-4" style="font-family: 'Libre Baskerville', serif;">
            🎉 Abonnement Activé avec Succès !
        </h1>
        
        <p class="text-xl text-[#8B7355] mb-6">
            Bienvenue dans le plan <strong class="text-[#ED5F1E]">{{ $plan->name }}</strong> !
        </p>
        
        <div class="inline-block px-6 py-3 bg-green-100 border-2 border-green-300 rounded-xl">
            <i class="fas fa-calendar-check text-green-600 mr-2"></i>
            <span class="font-semibold text-green-800">
                Prochain paiement : {{ now()->addMonth()->format('d/m/Y') }}
            </span>
        </div>
    </div>

    {{-- What's Next --}}
    <div class="premium-card mb-8">
        <h2 class="text-2xl font-bold text-[#2C1810] mb-6">
            <i class="fas fa-rocket text-[#ED5F1E] mr-2"></i>
            Prochaines Étapes
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('creator.products.create') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3 class="font-bold text-[#2C1810] mb-2">Créer un Produit</h3>
                <p class="text-sm text-[#8B7355]">Ajoutez vos premiers articles</p>
            </a>
            
            <a href="{{ route('creator.settings.shop') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h3 class="font-bold text-[#2C1810] mb-2">Personnaliser</h3>
                <p class="text-sm text-[#8B7355]">Configurez votre vitrine</p>
            </a>
            
            <a href="{{ route('creator.settings.payment') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3 class="font-bold text-[#2C1810] mb-2">Paiements</h3>
                <p class="text-sm text-[#8B7355]">Configurez vos versements</p>
            </a>
        </div>
    </div>

    {{-- Unlocked Features --}}
    <div class="premium-card mb-8">
        <h2 class="text-2xl font-bold text-[#2C1810] mb-6">
            <i class="fas fa-unlock text-green-600 mr-2"></i>
            Fonctionnalités Débloquées
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($plan->features)
                @foreach($plan->features as $feature)
                    <div class="feature-unlock">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-600 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-[#2C1810] mb-1">{{ $feature }}</h4>
                                <p class="text-sm text-[#8B7355]">Disponible immédiatement</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Subscription Details --}}
    <div class="premium-card mb-8">
        <h2 class="text-2xl font-bold text-[#2C1810] mb-6">
            <i class="fas fa-file-invoice text-[#ED5F1E] mr-2"></i>
            Détails de Votre Abonnement
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <div class="text-sm text-[#8B7355] mb-1">Plan Souscrit</div>
                <div class="text-xl font-bold text-[#2C1810]">{{ $plan->name }}</div>
            </div>
            
            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <div class="text-sm text-[#8B7355] mb-1">Montant Mensuel</div>
                <div class="text-xl font-bold text-[#2C1810]">{{ number_format($plan->price, 0, ',', ' ') }} FCFA</div>
            </div>
            
            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <div class="text-sm text-[#8B7355] mb-1">Date d'Activation</div>
                <div class="text-xl font-bold text-[#2C1810]">{{ now()->format('d/m/Y') }}</div>
            </div>
            
            <div class="p-4 bg-[#F8F6F3] rounded-xl">
                <div class="text-sm text-[#8B7355] mb-1">Prochain Paiement</div>
                <div class="text-xl font-bold text-[#2C1810]">{{ now()->addMonth()->format('d/m/Y') }}</div>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
                <div class="flex-1">
                    <h4 class="font-bold text-blue-900 mb-1">Gestion de l'Abonnement</h4>
                    <p class="text-sm text-blue-800 mb-2">
                        Vous pouvez modifier ou annuler votre abonnement à tout moment depuis vos paramètres.
                        L'annulation prend effet à la fin de la période en cours.
                    </p>
                    <a href="{{ route('creator.settings.payment') }}" class="text-sm text-blue-700 hover:text-blue-900 font-semibold underline">
                        Gérer mon abonnement →
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA Buttons --}}
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('creator.dashboard') }}" class="premium-btn text-center">
            <i class="fas fa-chart-pie mr-2"></i>
            Accéder au Tableau de Bord
        </a>
        
        <a href="{{ route('creator.products.create') }}" class="px-6 py-3 rounded-xl border-2 border-[#ED5F1E] text-[#ED5F1E] font-semibold hover:bg-[#ED5F1E] hover:text-white transition-colors text-center">
            <i class="fas fa-plus mr-2"></i>
            Créer Mon Premier Produit
        </a>
    </div>
</div>
@endsection
