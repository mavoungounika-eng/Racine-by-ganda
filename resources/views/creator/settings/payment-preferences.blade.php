@extends('layouts.creator')

@section('title', 'Préférences de Paiement - RACINE BY GANDA')
@section('page-title', 'Préférences de Paiement')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator/payment-preferences.css') }}">
@endpush

@section('content')
<div class="payment-preferences-container">
    
    {{-- HEADER --}}
    <div class="payment-header">
        <div>
            <h1 class="payment-title">Préférences de Paiement</h1>
            <p class="payment-subtitle">Gérez vos méthodes de paiement pour recevoir vos revenus</p>
        </div>
        @if($stripeAccount && $stripeAccount->payouts_enabled)
            <span class="status-badge status-badge--success">
                <i class="fas fa-check-circle"></i>
                Actif
            </span>
        @else
            <span class="status-badge status-badge--warning">
                <i class="fas fa-exclamation-triangle"></i>
                Action Requise
            </span>
        @endif
    </div>

    {{-- MESSAGES FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible">
            <i class="fas fa-exclamation-triangle"></i>
            {{ session('warning') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- STRIPE CONNECT CARD --}}
    <div class="payment-card {{ $stripeAccount && $stripeAccount->payouts_enabled ? 'payment-card--success' : 'payment-card--warning' }}">
        <div class="payment-card-header">
            <div class="payment-card-icon {{ $stripeAccount && $stripeAccount->payouts_enabled ? 'payment-card-icon--success' : 'payment-card-icon--warning' }}">
                <i class="fab fa-stripe"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="payment-card-title">
                    STRIPE CONNECT 
                    <span class="badge-principal">Principal</span>
                </h3>
                @if($stripeAccount && $stripeAccount->payouts_enabled)
                    <span class="status-badge status-badge--success status-badge--sm">
                        <i class="fas fa-check"></i>
                        Connecté
                    </span>
                @else
                    <span class="status-badge status-badge--warning status-badge--sm">
                        <i class="fas fa-exclamation-circle"></i>
                        Configuration requise
                    </span>
                @endif
            </div>
        </div>

        @if(!$stripeAccount || !$stripeAccount->payouts_enabled)
            {{-- État Inactif --}}
            <div class="alert alert-warning-light">
                <i class="fas fa-info-circle"></i>
                <strong>Aucun compte Stripe Connect configuré</strong>
            </div>
            <p class="payment-card-description">
                Connectez votre compte Stripe pour recevoir vos paiements automatiquement. Nécessaire pour l'activation des autres méthodes.
            </p>
            <div class="payment-card-actions">
                <form action="{{ route('creator.settings.payment-preferences.stripe.connect') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-payment btn-payment--primary">
                        Activer Stripe Connect
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                <a href="#" class="payment-link" data-bs-toggle="modal" data-bs-target="#howItWorksModal">
                    Comment ça marche?
                </a>
            </div>
        @else
            {{-- État Actif --}}
            <p class="payment-card-description">
                <i class="fas fa-check-circle text-success"></i>
                Vous recevez vos paiements automatiquement sur votre compte bancaire.
            </p>
            <div class="payment-card-actions">
                <a href="{{ route('creator.finances.index') }}" class="btn-payment btn-payment--secondary">
                    <i class="fas fa-chart-line"></i>
                    Voir mon dashboard Stripe
                </a>
                <button class="btn-icon" data-bs-toggle="tooltip" title="En savoir plus">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        @endif
    </div>

    {{-- MOBILE MONEY CARD --}}
    <div class="payment-card {{ $stripeAccount && $stripeAccount->payouts_enabled ? 'payment-card--info' : 'payment-card--disabled' }}">
        <div class="payment-card-header">
            <div class="payment-card-icon {{ $stripeAccount && $stripeAccount->payouts_enabled ? 'payment-card-icon--orange' : 'payment-card-icon--gray' }}">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="payment-card-title">
                    MOBILE MONEY 
                    <span class="badge-secondary">Secours</span>
                </h3>
                @if(!$stripeAccount || !$stripeAccount->payouts_enabled)
                    <span class="status-badge status-badge--disabled status-badge--sm">
                        Indisponible
                    </span>
                @elseif($preferences->hasMobileMoneyConfigured())
                    <span class="status-badge status-badge--success status-badge--sm">
                        <i class="fas fa-check"></i>
                        Configuré
                    </span>
                @else
                    <span class="status-badge status-badge--warning status-badge--sm">
                        <i class="fas fa-exclamation-triangle"></i>
                        Configuration recommandée
                    </span>
                @endif
            </div>
        </div>

        @if(!$stripeAccount || !$stripeAccount->payouts_enabled)
            <p class="payment-card-description text-muted">
                <i class="fas fa-lock"></i>
                Activez d'abord Stripe Connect.
            </p>
        @else
            <p class="payment-card-description">
                Moyen de secours en cas de problème Stripe. Les paiements locaux sont plus rapides.
            </p>

            <form action="{{ route('creator.settings.payment-preferences.mobile-money.save') }}" method="POST" id="mobileMoneyForm">
                @csrf
                <div class="form-group">
                    <label for="operator">Opérateur</label>
                    <select name="operator" id="operator" class="form-control @error('operator') is-invalid @enderror">
                        <option value="">Sélectionner...</option>
                        <option value="orange" {{ old('operator', $preferences->mobile_money_operator) == 'orange' ? 'selected' : '' }}>Orange Money</option>
                        <option value="mtn" {{ old('operator', $preferences->mobile_money_operator) == 'mtn' ? 'selected' : '' }}>MTN MoMo</option>
                        <option value="wave" {{ old('operator', $preferences->mobile_money_operator) == 'wave' ? 'selected' : '' }}>Wave</option>
                    </select>
                    @error('operator')
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Numéro de téléphone</label>
                    <input 
                        type="tel" 
                        name="phone" 
                        id="phone" 
                        class="form-control @error('phone') is-invalid @enderror"
                        placeholder="0XXXXXXXXX"
                        value="{{ old('phone', $preferences->mobile_money_number) }}"
                        maxlength="10"
                    >
                    @error('phone')
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn-payment btn-payment--primary">
                    <i class="fas fa-save"></i>
                    Sauvegarder
                </button>
            </form>
        @endif
    </div>

    {{-- FACTURATION INFO CARD --}}
    <div class="payment-card payment-card--info-light">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--blue">
                <i class="fas fa-receipt"></i>
            </div>
            <div>
                <h3 class="payment-card-title">FACTURATION INFO</h3>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-crown text-orange-500"></i>
                <div>
                    <div class="info-label">Plan Actuel</div>
                    <div class="info-value">Créateur Premium</div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-calendar-alt text-orange-500"></i>
                <div>
                    <div class="info-label">Prochain Paiement</div>
                    <div class="info-value">{{ $preferences->payout_schedule === 'automatic' ? 'Dans 7 jours' : 'Le 1er du mois' }}</div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-percentage text-orange-500"></i>
                <div>
                    <div class="info-label">Frais de Plateforme</div>
                    <div class="info-value">5% par transaction + 0.30€</div>
                </div>
            </div>
        </div>

        <a href="{{ route('creator.finances.index') }}" class="payment-link">
            Voir l'historique complet
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    {{-- LIEN VERS PARAMÈTRES AVANCÉS --}}
    <div class="text-center mt-4">
        <a href="{{ route('creator.settings.payment-preferences.advanced') }}" class="btn-payment btn-payment--secondary">
            <i class="fas fa-cog"></i>
            Paramètres Avancés
        </a>
    </div>

</div>

{{-- MODAL "Comment ça marche?" --}}
<div class="modal fade" id="howItWorksModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fab fa-stripe text-primary"></i>
                    Comment fonctionne Stripe Connect?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="feature-list">
                    <li><i class="fas fa-check text-success"></i> Paiements automatiques sous 7 jours</li>
                    <li><i class="fas fa-check text-success"></i> Sécurisé et conforme aux normes bancaires</li>
                    <li><i class="fas fa-check text-success"></i> Tableau de bord détaillé de vos revenus</li>
                    <li><i class="fas fa-check text-success"></i> Support 24/7</li>
                </ul>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    Vous serez redirigé vers Stripe pour compléter votre profil en toute sécurité.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-payment btn-payment--secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/creator/payment-preferences.js') }}"></script>
@endpush
