@extends('layouts.admin')

@section('title', 'Paramètres - RACINE BY GANDA')
@section('page_title', 'Paramètres')
@section('page_subtitle', 'Configuration globale du site')
@section('breadcrumb', 'Paramètres')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- INFORMATIONS GÉNÉRALES --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nom du site</label>
                    <input type="text" name="site_name" class="form-control" value="{{ $settings['site_name'] }}" placeholder="RACINE BY GANDA">
                    <small class="text-muted">Nom affiché sur tout le site</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email de contact</label>
                    <input type="email" name="site_email" class="form-control" value="{{ $settings['site_email'] }}" placeholder="contact@racine.cm">
                    <small class="text-muted">Email principal pour les notifications</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Téléphone</label>
                    <input type="text" name="site_phone" class="form-control" value="{{ old('site_phone', '') }}" placeholder="+237 6XX XX XX XX">
                    <small class="text-muted">Numéro de contact affiché</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Adresse physique</label>
                    <input type="text" name="site_address" class="form-control" value="{{ old('site_address', '') }}" placeholder="Douala, Cameroun">
                    <small class="text-muted">Adresse du magasin/bureau</small>
                </div>
            </div>
        </div>
    </div>

    {{-- RÉSEAUX SOCIAUX --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
            <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>Réseaux sociaux</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-facebook text-primary me-2"></i>Facebook</label>
                    <input type="url" name="social_facebook" class="form-control" value="{{ old('social_facebook', '') }}" placeholder="https://facebook.com/racinebyganda">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-instagram text-danger me-2"></i>Instagram</label>
                    <input type="url" name="social_instagram" class="form-control" value="{{ old('social_instagram', '') }}" placeholder="https://instagram.com/racinebyganda">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-twitter text-info me-2"></i>Twitter / X</label>
                    <input type="url" name="social_twitter" class="form-control" value="{{ old('social_twitter', '') }}" placeholder="https://twitter.com/racinebyganda">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-whatsapp text-success me-2"></i>WhatsApp</label>
                    <input type="text" name="social_whatsapp" class="form-control" value="{{ old('social_whatsapp', '') }}" placeholder="+237 6XX XX XX XX">
                    <small class="text-muted">Numéro WhatsApp Business</small>
                </div>
            </div>
        </div>
    </div>

    {{-- MARKETPLACE & COMMISSIONS --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
            <h5 class="mb-0"><i class="fas fa-store me-2"></i>Marketplace & Commissions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Taux de commission par défaut (%)</label>
                    <input type="number" name="commission_rate" class="form-control" value="{{ $settings['commission_rate'] }}" min="0" max="100" step="0.01">
                    <small class="text-muted">Commission prélevée sur les ventes des créateurs marketplace</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Frais de livraison par défaut (FCFA)</label>
                    <input type="number" name="shipping_fee" class="form-control" value="{{ old('shipping_fee', '2000') }}" min="0" step="100">
                    <small class="text-muted">Frais de livraison standard</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Devise</label>
                    <select name="currency" class="form-select">
                        <option value="FCFA" selected>FCFA (Franc CFA)</option>
                        <option value="EUR">EUR (Euro)</option>
                        <option value="USD">USD (Dollar)</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Seuil stock faible</label>
                    <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', '10') }}" min="1">
                    <small class="text-muted">Alerte si stock inférieur à cette valeur</small>
                </div>
            </div>
        </div>
    </div>

    {{-- PAIEMENT STRIPE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
            <h5 class="mb-0"><i class="fab fa-stripe text-primary me-2"></i>Configuration Stripe</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note :</strong> Les clés Stripe sont configurées dans le fichier <code>.env</code> pour des raisons de sécurité.
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mode Stripe</label>
                    <select name="stripe_mode" class="form-select">
                        <option value="test" selected>Mode Test</option>
                        <option value="live">Mode Production</option>
                    </select>
                    <small class="text-muted">Basculer entre test et production</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Activer les paiements</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="payments_enabled" id="paymentsEnabled" checked>
                        <label class="form-check-label" for="paymentsEnabled">
                            Autoriser les paiements en ligne
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAINTENANCE & SYSTÈME --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Système</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mode maintenance</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="maintenance" disabled {{ $settings['maintenance_mode'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="maintenance">
                            Site en maintenance
                        </label>
                    </div>
                    <small class="text-muted">Utilisez <code>php artisan down</code> / <code>php artisan up</code></small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Activer les inscriptions</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="registrations_enabled" id="registrationsEnabled" checked>
                        <label class="form-check-label" for="registrationsEnabled">
                            Autoriser les nouvelles inscriptions
                        </label>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Message de maintenance</label>
                    <textarea name="maintenance_message" class="form-control" rows="3" placeholder="Le site est temporairement en maintenance. Nous revenons bientôt !">{{ old('maintenance_message', '') }}</textarea>
                    <small class="text-muted">Message affiché pendant la maintenance</small>
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Enregistrer les paramètres
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
    </div>
</form>

@endsection
