@extends('layouts.creator')

@section('title', 'Configuration Paiements Directs - RACINE BY GANDA')
@section('page-title', 'Passerelles de Paiement')

@section('content')
<div class="container-fluid px-4">
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <div class="fs-4 me-3 text-primary"><i class="fas fa-shield-alt"></i></div>
            <div>
                <h5 class="alert-heading mb-1">Modèle SaaS Pur : Paiements Directs</h5>
                <p class="mb-0 text-muted">
                    RACINE agit comme un pur facilitateur technique. En configurant vos propres passerelles, 
                    <strong>l'argent de vos ventes arrive directement sur votre compte</strong> sans passer par RACINE.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="show"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="show"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- STRIPE DIRECT --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary-soft text-primary rounded-circle me-3">
                                <i class="fab fa-stripe fs-4"></i>
                            </div>
                            <h5 class="mb-0">Stripe Direct (CB)</h5>
                        </div>
                        @if($preferences->stripe_secret_key)
                            <span class="badge bg-success-soft text-success"><i class="fas fa-check"></i> Connecté</span>
                        @else
                            <span class="badge bg-danger-soft text-danger">Déconnecté</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('creator.settings.payment-preferences.stripe.connect') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted">Stripe Secret Key (sk_...)</label>
                            <input type="password" name="stripe_secret_key" class="form-control" 
                                   value="{{ $preferences->stripe_secret_key ? '********' : '' }}" required>
                            <div class="form-text">Clé commençant par <code>sk_live_</code> ou <code>sk_test_</code>.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Stripe Publishable Key (pk_...)</label>
                            <input type="text" name="stripe_publishable_key" class="form-control" 
                                   value="{{ $preferences->stripe_publishable_key }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Tester et Enregistrer Stripe
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light border-0 py-3">
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i> Vos clés sont encryptées avant stockage.
                    </small>
                </div>
            </div>
        </div>

        {{-- MONETBIL DIRECT --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-warning-soft text-warning rounded-circle me-3">
                                <i class="fas fa-mobile-alt fs-4"></i>
                            </div>
                            <h5 class="mb-0">Monetbil (Mobile Money)</h5>
                        </div>
                        @if($preferences->momo_api_key)
                            <span class="badge bg-success-soft text-success"><i class="fas fa-check"></i> Configuré</span>
                        @else
                            <span class="badge bg-danger-soft text-danger">Non configuré</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('creator.settings.payment-preferences.mobile-money.save') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted">Service Key</label>
                            <input type="text" name="momo_provider" class="form-control" 
                                   value="{{ $preferences->momo_provider }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Service Secret / API Key</label>
                            <input type="password" name="momo_api_key" class="form-control" 
                                   value="{{ $preferences->momo_api_key ? '********' : '' }}" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 text-white">
                            Enregistrer Monetbil
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light border-0 py-3">
                    <small class="text-muted">
                        Utilisé pour Orange Money, MTN MoMo et Airtel Money localement.
                    </small>
                </div>
            </div>
        </div>

        {{-- ZONE DE DANGER --}}
        @if($preferences->stripe_secret_key || $preferences->momo_api_key)
        <div class="col-12 mt-4">
            <div class="card border-danger-soft bg-danger-soft shadow-none">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-danger mb-1">Désactiver mes passerelles</h6>
                        <p class="mb-0 text-muted small">Vos produits ne pourront plus être achetés en ligne tant qu'aucune passerelle n'est active.</p>
                    </div>
                    <form action="{{ route('creator.settings.payment-preferences.stripe.disconnect') }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment déconnecter vos paiements directs ?');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">Déconnecter tout</button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style nonce="{{ csp_nonce() }}">
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .avatar-sm { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
</style>
@endsection
