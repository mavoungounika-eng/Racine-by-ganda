@extends('layouts.admin')

@section('title', 'Détails KYC - Admin')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.kyc.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i>Retour au dashboard KYC
        </a>
    </div>

    <h1 class="h3 mb-4">Détails KYC : {{ $creator->name }}</h1>

    <div class="row">
        {{-- Informations Créateur --}}
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations Créateur</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-circle mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #ED5F1E, #FFB800); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold;">
                            {{ strtoupper(substr($creator->name, 0, 1)) }}
                        </div>
                    </div>
                    <h5 class="text-center mb-3">{{ $creator->name }}</h5>
                    
                    <div class="mb-2">
                        <small class="text-muted">Email</small>
                        <p class="mb-0">{{ $creator->email }}</p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Nom de la boutique</small>
                        <p class="mb-0">{{ $creator->creatorProfile->brand_name ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Membre depuis</small>
                        <p class="mb-0">{{ $creator->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($stripeAccount)
                        <form action="{{ route('admin.kyc.sync', $creator) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sync mr-2"></i>Synchroniser avec Stripe
                            </button>
                        </form>
                    @else
                        <p class="text-muted text-center mb-0">Aucun compte Stripe Connect</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Statut KYC --}}
        <div class="col-lg-8">
            @if($stripeAccount)
                {{-- Vue d'ensemble --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Vue d'ensemble KYC</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 rounded {{ $kycStatus['onboarding_complete'] ? 'bg-success' : 'bg-warning' }} text-white">
                                    <i class="fas fa-{{ $kycStatus['onboarding_complete'] ? 'check-circle' : 'clock' }} fa-2x mb-2"></i>
                                    <p class="mb-0 small">Onboarding</p>
                                    <strong>{{ $kycStatus['onboarding_complete'] ? 'Complet' : 'En cours' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 rounded {{ $kycStatus['details_submitted'] ? 'bg-success' : 'bg-danger' }} text-white">
                                    <i class="fas fa-{{ $kycStatus['details_submitted'] ? 'file-alt' : 'times' }} fa-2x mb-2"></i>
                                    <p class="mb-0 small">Documents</p>
                                    <strong>{{ $kycStatus['details_submitted'] ? 'Soumis' : 'Manquants' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 rounded {{ $kycStatus['payouts_enabled'] ? 'bg-success' : 'bg-danger' }} text-white">
                                    <i class="fas fa-{{ $kycStatus['payouts_enabled'] ? 'wallet' : 'ban' }} fa-2x mb-2"></i>
                                    <p class="mb-0 small">Paiements</p>
                                    <strong>{{ $kycStatus['payouts_enabled'] ? 'Activés' : 'Désactivés' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 rounded {{ $kycStatus['charges_enabled'] ? 'bg-success' : 'bg-danger' }} text-white">
                                    <i class="fas fa-{{ $kycStatus['charges_enabled'] ? 'credit-card' : 'ban' }} fa-2x mb-2"></i>
                                    <p class="mb-0 small">Charges</p>
                                    <strong>{{ $kycStatus['charges_enabled'] ? 'Activées' : 'Désactivées' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Détails du compte Stripe --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Détails du Compte Stripe</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Stripe Account ID</small>
                                <p class="mb-0"><code>{{ $stripeAccount->stripe_account_id }}</code></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Statut Onboarding</small>
                                <p class="mb-0">
                                    <span class="badge badge-{{ $stripeAccount->onboarding_status === 'complete' ? 'success' : 'warning' }}">
                                        {{ ucfirst($stripeAccount->onboarding_status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Dernière synchronisation</small>
                                <p class="mb-0">{{ $stripeAccount->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Compte créé le</small>
                                <p class="mb-0">{{ $stripeAccount->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Documents requis --}}
                @if(isset($kycStatus['requirements']) && !empty($kycStatus['requirements']))
                    <div class="card shadow-sm border-warning">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Documents Requis</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Les documents suivants sont nécessaires pour compléter la vérification :</p>
                            <ul class="mb-0">
                                @foreach($kycStatus['requirements'] as $requirement)
                                    <li><code>{{ $requirement }}</code></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @else
                <div class="card shadow-sm border-danger">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                        <h4>Aucun Compte Stripe Connect</h4>
                        <p class="text-muted">Ce créateur n'a pas encore configuré son compte Stripe Connect.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
