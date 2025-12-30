@extends('layouts.admin-master')

@section('title', 'Payments Hub - RACINE BY GANDA')
@section('page-title', 'Payments Hub')
@section('page-subtitle', 'Centre de pilotage des paiements')

@section('content')

{{-- KPIs Cards --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Transactions</h6>
                        <h3 class="mb-0">{{ number_format($kpis['total'], 0, ',', ' ') }}</h3>
                    </div>
                    <div class="text-primary" style="font-size: 2rem;">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Réussies</h6>
                        <h3 class="mb-0 text-success">{{ number_format($kpis['succeeded'], 0, ',', ' ') }}</h3>
                    </div>
                    <div class="text-success" style="font-size: 2rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Échouées</h6>
                        <h3 class="mb-0 text-danger">{{ number_format($kpis['failed'], 0, ',', ' ') }}</h3>
                    </div>
                    <div class="text-danger" style="font-size: 2rem;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Taux de succès</h6>
                        <h3 class="mb-0">{{ number_format($kpis['success_rate'], 2, ',', ' ') }}%</h3>
                    </div>
                    <div class="text-info" style="font-size: 2rem;">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Montant total et panier moyen --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-2">Montant total</h6>
                <h2 class="mb-0">{{ number_format($kpis['total_amount'], 0, ',', ' ') }} FCFA</h2>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-2">Panier moyen</h6>
                <h2 class="mb-0">{{ number_format($kpis['avg_cart'], 0, ',', ' ') }} FCFA</h2>
            </div>
        </div>
    </div>
</div>

{{-- Webhooks Health / Observability (Patch 4.3) --}}
@if(isset($webhookMetrics))
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line text-info me-2"></i>
                    Webhooks Health / Observability
                </h5>
                <a href="{{ route('admin.payments.webhooks.stuck.index') }}" class="btn btn-sm btn-outline-warning">
                    Stuck Webhooks <i class="fas fa-exclamation-triangle ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Stripe --}}
                    <div class="col-lg-6 mb-3">
                        <h6 class="text-muted mb-3">
                            <i class="fab fa-stripe text-primary me-2"></i>
                            Stripe
                        </h6>
                        <div class="row text-center g-3">
                            <div class="col-3">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <div class="small text-muted">Received</div>
                                        <div class="h5 mb-0">{{ $webhookMetrics['counts_by_status']['stripe']['received'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <div class="small text-muted">Processed</div>
                                        <div class="h5 mb-0 text-success">{{ $webhookMetrics['counts_by_status']['stripe']['processed'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <div class="small text-muted">Failed</div>
                                        <div class="h5 mb-0 text-danger">{{ $webhookMetrics['counts_by_status']['stripe']['failed'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card bg-warning">
                                    <div class="card-body p-2">
                                        <div class="small text-dark">Stuck</div>
                                        <div class="h5 mb-0 text-dark">{{ $webhookMetrics['stuck_counts']['stripe']['total'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Monetbil --}}
                    <div class="col-lg-6 mb-3">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-mobile-alt text-info me-2"></i>
                            Monetbil
                        </h6>
                        <div class="row text-center g-3">
                            <div class="col-3">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <div class="small text-muted">Received</div>
                                        <div class="h5 mb-0">{{ $webhookMetrics['counts_by_status']['monetbil']['received'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <div class="small text-muted">Processed</div>
                                        <div class="h5 mb-0 text-success">{{ $webhookMetrics['counts_by_status']['monetbil']['processed'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <div class="small text-muted">Failed</div>
                                        <div class="h5 mb-0 text-danger">{{ $webhookMetrics['counts_by_status']['monetbil']['failed'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card bg-warning">
                                    <div class="card-body p-2">
                                        <div class="small text-dark">Stuck</div>
                                        <div class="h5 mb-0 text-dark">{{ $webhookMetrics['stuck_counts']['monetbil']['total'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(isset($webhookMetrics['blocked_counts']['monetbil']) && $webhookMetrics['blocked_counts']['monetbil'] > 0)
                        <div class="row text-center g-3 mt-2">
                            <div class="col-12">
                                <div class="alert alert-dark mb-0 py-2">
                                    <i class="fas fa-ban"></i>
                                    <strong>Blocked:</strong> {{ $webhookMetrics['blocked_counts']['monetbil'] }}
                                    @if(isset($webhookMetrics['average_latency_seconds']['monetbil']))
                                        | <strong>Latence moyenne:</strong> {{ number_format($webhookMetrics['average_latency_seconds']['monetbil'], 2) }}s
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        Window: {{ $webhookMetrics['window_minutes'] }} min | 
                        Threshold: {{ $webhookMetrics['threshold_minutes'] }} min
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Santé Providers --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-server text-racine-orange me-2"></i>
                    Santé des Providers
                </h5>
                <a href="{{ route('admin.payments.providers.index') }}" class="btn btn-sm btn-outline-racine-orange">
                    Gérer <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Statut</th>
                                <th>Configuration</th>
                                <th>Santé</th>
                                <th>Dernier événement</th>
                                <th>Priorité</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($providers as $provider)
                                @php
                                    $configStatus = app(\App\Services\Payments\ProviderConfigStatusService::class)->checkConfigStatus($provider->code);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $provider->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $provider->code }}</small>
                                    </td>
                                    <td>
                                        @if($provider->is_enabled)
                                            <span class="badge badge-success">Actif</span>
                                        @else
                                            <span class="badge badge-secondary">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($configStatus['status'] === 'ok')
                                            <span class="badge badge-success">OK</span>
                                        @else
                                            <span class="badge badge-danger">KO</span>
                                            <small class="d-block text-muted">{{ $configStatus['message'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($provider->health_status === 'ok')
                                            <span class="badge badge-success">OK</span>
                                        @elseif($provider->health_status === 'degraded')
                                            <span class="badge badge-warning">Dégradé</span>
                                        @else
                                            <span class="badge badge-danger">Down</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($provider->last_event_at)
                                            <small>{{ $provider->last_event_at->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Jamais</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $provider->priority }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Aucun provider configuré</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Derniers événements --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-history text-racine-orange me-2"></i>
                    Derniers événements
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Type</th>
                                <th>ID événement</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEvents as $event)
                                <tr>
                                    <td>
                                        <span class="badge badge-{{ $event['provider'] === 'stripe' ? 'primary' : 'info' }}">
                                            {{ ucfirst($event['provider']) }}
                                        </span>
                                    </td>
                                    <td>{{ $event['event_type'] ?? 'N/A' }}</td>
                                    <td>
                                        <small class="font-monospace">{{ $event['event_id'] ?? $event['event_key'] ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($event['status'] === 'processed')
                                            <span class="badge badge-success">Traité</span>
                                        @elseif($event['status'] === 'failed')
                                            <span class="badge badge-danger">Échoué</span>
                                        @else
                                            <span class="badge badge-warning">{{ ucfirst($event['status']) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($event['created_at'])->diffForHumans() }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucun événement récent</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection




