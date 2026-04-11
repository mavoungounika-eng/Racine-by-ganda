@extends('layouts.admin')

@section('title', 'Détails Transaction Mobile Money - Admin')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.mobile-money.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
        </a>
    </div>

    <h1 class="h3 mb-4">Transaction Mobile Money #{{ $subscription->id }}</h1>

    <div class="row">
        {{-- Informations Transaction --}}
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Détails de la Transaction</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">ID Transaction</small>
                            <h5 class="mb-0">#{{ str_pad($subscription->id, 8, '0', STR_PAD_LEFT) }}</h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Statut</small>
                            <p class="mb-0">
                                @if($subscription->status === 'active')
                                    <span class="badge badge-success badge-lg">Actif</span>
                                @elseif($subscription->status === 'canceled')
                                    <span class="badge badge-danger badge-lg">Annulé</span>
                                @else
                                    <span class="badge badge-warning badge-lg">{{ ucfirst($subscription->status) }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Plan Souscrit</small>
                            <h5 class="mb-0">{{ $subscription->plan->name }}</h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Montant</small>
                            <h5 class="mb-0 text-primary">{{ number_format($subscription->plan->price, 0, ',', ' ') }} FCFA</h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Date de Souscription</small>
                            <p class="mb-0">{{ $subscription->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Période</small>
                            <p class="mb-0">
                                Du {{ $subscription->current_period_start?->format('d/m/Y') ?? 'N/A' }}
                                <br>Au {{ $subscription->current_period_end?->format('d/m/Y') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informations Créateur --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations Créateur</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Nom</small>
                            <p class="mb-0"><strong>{{ $subscription->creatorProfile->user->name }}</strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Email</small>
                            <p class="mb-0">{{ $subscription->creatorProfile->user->email }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Boutique</small>
                            <p class="mb-0">{{ $subscription->creatorProfile->brand_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Téléphone</small>
                            <p class="mb-0">{{ $subscription->creatorProfile->user->phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.creators.show', $subscription->creatorProfile->user->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user mr-1"></i>Voir le profil complet
                        </a>
                    </div>
                </div>
            </div>

            {{-- Historique --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Historique</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <p class="mb-1"><strong>Transaction créée</strong></p>
                                <small class="text-muted">{{ $subscription->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        @if($subscription->status === 'active')
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <p class="mb-1"><strong>Transaction validée</strong></p>
                                    <small class="text-muted">{{ $subscription->updated_at->format('d/m/Y H:i') }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($subscription->status !== 'active')
                        <form action="{{ route('admin.mobile-money.validate', $subscription) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-check mr-2"></i>Valider la Transaction
                            </button>
                        </form>
                    @endif

                    @if($subscription->status === 'active')
                        <form action="{{ route('admin.mobile-money.reject', $subscription) }}" method="POST" 
                              onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette transaction ?');">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-times mr-2"></i>Rejeter la Transaction
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Résumé Plan --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Résumé du Plan</h5>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">{{ $subscription->plan->name }}</h5>
                    <p class="text-muted mb-3">{{ $subscription->plan->description }}</p>
                    
                    <div class="mb-3">
                        <small class="text-muted">Prix mensuel</small>
                        <h4 class="mb-0 text-primary">{{ number_format($subscription->plan->price, 0, ',', ' ') }} FCFA</h4>
                    </div>

                    @if($subscription->plan->features)
                        <small class="text-muted">Fonctionnalités incluses :</small>
                        <ul class="small mt-2">
                            @foreach($subscription->plan->features as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 12px;
    bottom: -8px;
    width: 2px;
    background: #dee2e6;
}
</style>
@endpush
@endsection
