@extends('layouts.creator')

@section('title', 'Paramètres de Paiement - RACINE BY GANDA')
@section('page-title', 'Paramètres de Paiement')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .payment-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid #F0EBE5;
        height: 100%;
        transition: all 0.3s ease;
    }

    .payment-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--racine-orange);
    }

    .payment-icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
    }

    .stripe-icon { background: #F6F9FC; color: #635BFF; }
    .momo-icon { background: #FFF7ED; color: #ED5F1E; }

    .card-title-premium {
        font-family: 'Libre Baskerville', serif;
        font-weight: 700;
        color: var(--racine-black);
        margin-bottom: 0.5rem;
    }

    .badge-premium {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bg-success-premium { background: #DCFCE7; color: #166534; }
    .bg-warning-premium { background: #FEF3C7; color: #92400E; }
    .bg-info-premium { background: #E0F2FE; color: #075985; }

    .payment-info-box {
        background: #F8F6F3;
        border-radius: 15px;
        padding: 1.5rem;
        border: 1px solid #E5DDD3;
        margin-top: 2rem;
    }

    .info-item {
        margin-bottom: 1rem;
    }

    .info-label {
        color: #8B7355;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    .info-value {
        color: var(--racine-black);
        font-weight: 600;
        display: block;
    }

    .creator-label {
        font-weight: 700;
        color: var(--racine-black) !important;
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    
    {{-- Navigation Unifiée --}}
    @include('creator.partials.settings-nav')

    <div class="mb-5">
        <h2 class="h3 font-weight-bold" style="color: var(--racine-black); font-family: 'Libre Baskerville', serif;">
            Configuration des paiements
        </h2>
        <p class="text-muted font-weight-bold">Choisissez comment vous souhaitez recevoir vos revenus de créateur.</p>
    </div>

    {{-- MESSAGES --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4 rounded-pill px-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-pill px-4">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row">
        {{-- STRIPE CONNECT --}}
        <div class="col-lg-6 mb-4">
            <div class="payment-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div class="payment-icon-wrapper stripe-icon shadow-sm">
                        <i class="fab fa-stripe"></i>
                    </div>
                    @if($stripeAccount && $stripeAccount->payouts_enabled)
                        <span class="badge-premium bg-success-premium">
                            <i class="fas fa-check-circle me-1"></i> Compte Actif
                        </span>
                    @else
                        <span class="badge-premium bg-warning-premium">
                            <i class="fas fa-clock me-1"></i> À configurer
                        </span>
                    @endif
                </div>

                <h3 class="card-title-premium h4">Stripe Connect</h3>
                <p class="text-muted">Standard mondial pour les paiements en ligne. Recommandé pour les virements automatiques.</p>

                <div class="mt-4">
                    @if(!$stripeAccount || !$stripeAccount->payouts_enabled)
                        <div class="alert bg-warning border-0 px-3 py-3" style="background-color: #FEF3C7 !important;">
                            <p class="small mb-0 font-weight-bold" style="color: #92400E !important;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Votre compte Stripe n'est pas encore actif. Connectez-vous pour automatiser vos virements bancaires.
                            </p>
                        </div>
                    @else
                        {{-- ... (reste inchangé) --}}
                    @endif

                    <form action="{{ route('creator.settings.payment-preferences.stripe.connect') }}" method="POST" class="{{ $stripeAccount && $stripeAccount->payouts_enabled ? 'd-none' : '' }}">
                        @csrf
                        <button type="submit" class="btn creator-btn w-100 py-3">
                            <i class="fas fa-link me-2"></i> Configurer mon compte Stripe
                        </button>
                    </form>

                    @if($stripeAccount && $stripeAccount->payouts_enabled)
                        <div class="info-item border-bottom pb-2 mb-3">
                            <span class="info-label">Identifiant Compte</span>
                            <span class="info-value">{{ $stripeAccount->stripe_account_id }}</span>
                        </div>
                        <div class="info-item mb-4">
                            <span class="info-label">Statut des virements</span>
                            <span class="info-value {{ $stripeAccount->charges_enabled ? 'text-success' : 'text-warning' }}">
                                {{ $stripeAccount->charges_enabled ? 'Opérations prêtes' : 'Vérification en cours' }}
                            </span>
                        </div>
                        <a href="{{ route('creator.finances.index') }}" class="btn btn-outline-dark w-100 rounded-pill font-weight-bold">
                            <i class="fas fa-external-link-alt me-2"></i> Gérer via le Dashboard
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- MOBILE MONEY (SECOURS) --}}
        <div class="col-lg-6 mb-4">
            <div class="payment-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div class="payment-icon-wrapper momo-icon shadow-sm">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <span class="badge-premium bg-info-premium">Méthode Locales</span>
                </div>

                <h3 class="card-title-premium h4">Mobile Money</h3>
                <p class="text-muted">Recevez vos fonds directement sur votre numéro Orange, MTN, Moov ou Wave.</p>

                <div class="mt-4">
                    {{-- On permet désormais la config MoMo même si Stripe est en attente, mais avec un avertissement --}}
                    @if(!$stripeAccount || !$stripeAccount->payouts_enabled)
                        <div class="alert border-0 px-3 py-3 mb-4" style="background-color: #E0F2FE; color: #075985;">
                            <p class="small mb-0 font-weight-bold">
                                <i class="fas fa-info-circle me-2"></i>
                                Note : Stripe est recommandé pour les virements automatiques, mais vous pouvez configurer votre MoMo pour les retraits manuels.
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('creator.settings.payment.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="payout_method" value="mobile_money">

                        <div class="form-group mb-3">
                            <label class="creator-label">Opérateur</label>
                            <select name="mobile_money_provider" class="form-control creator-input h-auto py-3 @error('mobile_money_provider') is-invalid @enderror">
                                <option value="">Choisir un opérateur</option>
                                <option value="orange" {{ old('mobile_money_provider', $profile->payout_details['mobile_money']['provider'] ?? '') == 'orange' ? 'selected' : '' }}>Orange Money</option>
                                <option value="mtn" {{ old('mobile_money_provider', $profile->payout_details['mobile_money']['provider'] ?? '') == 'mtn' ? 'selected' : '' }}>MTN MoMo</option>
                                <option value="moov" {{ old('mobile_money_provider', $profile->payout_details['mobile_money']['provider'] ?? '') == 'moov' ? 'selected' : '' }}>Moov Money</option>
                                <option value="wave" {{ old('mobile_money_provider', $profile->payout_details['mobile_money']['provider'] ?? '') == 'wave' ? 'selected' : '' }}>Wave</option>
                            </select>
                            @error('mobile_money_provider') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="creator-label">Numéro de téléphone</label>
                            <input type="tel" name="mobile_money_number" class="form-control creator-input h-auto py-3 @error('mobile_money_number') is-invalid @enderror" 
                                   placeholder="06 XXX XXX" value="{{ old('mobile_money_number', $profile->payout_details['mobile_money']['number'] ?? '') }}">
                            @error('mobile_money_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="creator-label d-flex justify-content-between">
                                Seuil de versement (F CFA)
                                <span class="badge bg-info-premium shadow-none">Min. 5 000 F</span>
                            </label>
                            <input type="number" name="minimum_payout_threshold" class="form-control creator-input h-auto py-3 @error('minimum_payout_threshold') is-invalid @enderror" 
                                   placeholder="5000" min="5000" step="1000"
                                   value="{{ old('minimum_payout_threshold', $preferences->minimum_payout_threshold ?? 5000) }}">
                            <small class="text-muted">Vos gains seront transférés dès ce montant atteint.</small>
                            @error('minimum_payout_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill font-weight-bold shadow-sm">
                            <i class="fas fa-save me-2"></i> Sauvegarder mes coordonnées MoMo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- INFORMATIONS --}}
    <div class="payment-info-box shadow-sm">
        <h4 class="h5 font-weight-bold mb-4" style="color: var(--racine-black); font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-info-circle text-orange me-2"></i> À savoir sur vos revenus
        </h4>
        <div class="row">
            <div class="col-md-3 mb-4 mb-md-0">
                <span class="info-label">Commission RACINE</span>
                <span class="info-value">20% par vente</span>
                <p class="small text-muted mt-1">Maintenance de la plateforme.</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <span class="info-label">Seuil de retrait</span>
                <span class="info-value">Dès 5 000 FCFA</span>
                <p class="small text-muted mt-1">Montant min. accumulé.</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <span class="info-label">Délai de traitement</span>
                <span class="info-value">7 jours glissants</span>
                <p class="small text-muted mt-1">Sécurité anti-fraude.</p>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white rounded-lg border">
                    <span class="info-label text-warning"><i class="fas fa-shield-alt me-1"></i> Mode Test Actif</span>
                    <p class="small text-muted mb-0">Les transactions Stripe ne sont pas réelles pour le moment.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


