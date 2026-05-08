@extends('layouts.creator')

@section('title', 'Mon Abonnement Actuel - RACINE BY GANDA')
@section('page-title', 'Mon Abonnement')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .subscription-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid rgba(0,0,0,0.05);
    }
    .plan-badge {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .plan-badge.active { background: #d1fae5; color: #065f46; }
    .plan-badge.trialing { background: #fef9c3; color: #713f12; }
    .plan-badge.canceled { background: #fee2e2; color: #991b1b; }
    .plan-badge.free { background: #f3f4f6; color: #374151; }
    .capability-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .capability-row:last-child { border-bottom: none; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- Carte abonnement actuel --}}
            <div class="subscription-card mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1">{{ $plan?->name ?? 'Plan Gratuit' }}</h4>
                        @if($subscription && $subscription->status)
                            <span class="plan-badge {{ $subscription->status }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        @else
                            <span class="plan-badge free">Gratuit</span>
                        @endif
                    </div>
                    <div class="text-end">
                        @if($plan && $plan->price > 0)
                            <div class="fs-4 fw-bold" style="color: var(--racine-orange);">
                                {{ number_format($plan->price, 0, ',', ' ') }} XAF
                                <small class="fs-6 fw-normal text-muted">/mois</small>
                            </div>
                        @else
                            <div class="fs-4 fw-bold text-muted">Gratuit</div>
                        @endif
                    </div>
                </div>

                @if($subscription)
                    <div class="row text-center mt-4 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Début</div>
                            <div class="fw-semibold">{{ $subscription->started_at?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Expiration</div>
                            <div class="fw-semibold">
                                {{ $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : 'Sans limite' }}
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mt-2 mt-md-0">
                            <div class="text-muted small">Renouvellement</div>
                            <div class="fw-semibold">
                                @if($subscription->ends_at && $subscription->ends_at->isFuture())
                                    {{ $subscription->ends_at->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mt-2 mt-md-0">
                            <div class="text-muted small">Statut</div>
                            <div class="fw-semibold">{{ $subscription->isActive() ? 'Actif' : 'Inactif' }}</div>
                        </div>
                    </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('creator.subscription.upgrade') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-up-circle me-1"></i>Changer de plan
                    </a>
                    @if($subscription && $subscription->isActive())
                        <a href="{{ route('creator.subscription.plans') }}" class="btn btn-outline-secondary btn-sm">
                            Voir les offres
                        </a>
                    @endif
                </div>
            </div>

            {{-- Capacités --}}
            @if(!empty($capabilities))
            <div class="subscription-card">
                <h5 class="fw-bold mb-3">Fonctionnalités incluses</h5>
                @foreach($capabilities as $key => $value)
                    <div class="capability-row">
                        <span class="text-muted small">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                        <span class="fw-semibold">
                            @if(is_bool($value))
                                @if($value)
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                @endif
                            @elseif($value === -1 || $value === 'unlimited')
                                <span class="text-success">Illimité</span>
                            @else
                                {{ $value }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
