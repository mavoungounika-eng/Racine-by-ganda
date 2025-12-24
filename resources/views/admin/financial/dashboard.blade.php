@extends('layouts.admin')

@section('title', 'Dashboard Financier - RACINE BY GANDA')
@section('page_title', 'Dashboard Financier')
@section('page_subtitle', 'Pilotage stratégique des abonnements créateurs')

@section('content')

{{-- Sélecteur de mois --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.financial.dashboard') }}" class="d-flex align-items-center gap-3">
                    <label for="month" class="mb-0">Mois :</label>
                    <input type="month" 
                           name="month" 
                           id="month" 
                           value="{{ $month }}" 
                           class="form-control" 
                           style="max-width: 200px;"
                           onchange="this.form.submit()">
                    <button type="submit" class="btn btn-primary">Actualiser</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Revenus --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">MRR</h6>
                        <h3 class="mb-0 text-success">
                            {{ number_format($dashboardMetrics['revenue']['mrr'], 0, ',', ' ') }} XAF
                        </h3>
                        <small class="text-muted">Monthly Recurring Revenue</small>
                    </div>
                    <div class="text-success" style="font-size: 2.5rem;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">ARR</h6>
                        <h3 class="mb-0 text-primary">
                            {{ number_format($dashboardMetrics['revenue']['arr'], 0, ',', ' ') }} XAF
                        </h3>
                        <small class="text-muted">Annual Recurring Revenue</small>
                    </div>
                    <div class="text-primary" style="font-size: 2.5rem;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Revenu Net</h6>
                        <h3 class="mb-0 text-info">
                            {{ number_format($dashboardMetrics['revenue']['net_revenue'], 0, ',', ' ') }} XAF
                        </h3>
                        <small class="text-muted">Revenu plateforme</small>
                    </div>
                    <div class="text-info" style="font-size: 2.5rem;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Abonnements Actifs</h6>
                        <h3 class="mb-0 text-warning">
                            {{ number_format($dashboardMetrics['subscriptions']['active'], 0, ',', ' ') }}
                        </h3>
                        <small class="text-muted">{{ $dashboardMetrics['subscriptions']['canceled_this_month'] }} annulés ce mois</small>
                    </div>
                    <div class="text-warning" style="font-size: 2.5rem;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Créateurs --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Créateurs Actifs</h6>
                <h3 class="mb-0">{{ $dashboardMetrics['creators']['active'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-danger">
            <div class="card-body">
                <h6 class="text-muted mb-2">Créateurs Bloqués</h6>
                <h3 class="mb-0 text-danger">{{ $dashboardMetrics['creators']['blocked']['total'] }}</h3>
                <small class="text-muted">
                    {{ $dashboardMetrics['creators']['blocked']['stripe'] }} Stripe / 
                    {{ $dashboardMetrics['creators']['blocked']['subscription'] }} Abonnement
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-warning">
            <div class="card-body">
                <h6 class="text-muted mb-2">En Onboarding</h6>
                <h3 class="mb-0 text-warning">{{ $dashboardMetrics['creators']['in_onboarding'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-danger">
            <div class="card-body">
                <h6 class="text-muted mb-2">En Risque</h6>
                <h3 class="mb-0 text-danger">{{ $dashboardMetrics['creators']['at_risk'] }}</h3>
                <small class="text-muted">Abonnements past_due</small>
            </div>
        </div>
    </div>
</div>

{{-- Métriques Stratégiques (BI) --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Métriques Stratégiques (BI)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6 class="text-muted">Churn Rate</h6>
                        <h4 class="{{ $strategicMetrics['churn_rate'] > 10 ? 'text-danger' : ($strategicMetrics['churn_rate'] > 5 ? 'text-warning' : 'text-success') }}">
                            {{ number_format($strategicMetrics['churn_rate'], 2) }}%
                        </h4>
                        <small class="text-muted">
                            @if($strategicMetrics['churn_rate'] > 10)
                                ⚠️ Préoccupant
                            @elseif($strategicMetrics['churn_rate'] > 5)
                                ⚠️ Acceptable
                            @else
                                ✅ Excellent
                            @endif
                        </small>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-muted">ARPU</h6>
                        <h4 class="text-primary">
                            {{ number_format($strategicMetrics['arpu'], 0, ',', ' ') }} XAF
                        </h4>
                        <small class="text-muted">Revenu moyen par créateur</small>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-muted">LTV</h6>
                        <h4 class="text-info">
                            {{ number_format($strategicMetrics['ltv'], 0, ',', ' ') }} XAF
                        </h4>
                        <small class="text-muted">Valeur totale créateur</small>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-muted">Taux d'Activation</h6>
                        <h4 class="{{ $strategicMetrics['activation_rate'] > 80 ? 'text-success' : 'text-warning' }}">
                            {{ number_format($strategicMetrics['activation_rate'], 2) }}%
                        </h4>
                        <small class="text-muted">Onboarding complet</small>
                    </div>
                </div>

                <hr>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6 class="text-muted">Stripe Health Score</h6>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar 
                                {{ $strategicMetrics['stripe_health_score']['score'] > 90 ? 'bg-success' : ($strategicMetrics['stripe_health_score']['score'] > 70 ? 'bg-warning' : 'bg-danger') }}" 
                                role="progressbar" 
                                style="width: {{ $strategicMetrics['stripe_health_score']['score'] }}%"
                                aria-valuenow="{{ $strategicMetrics['stripe_health_score']['score'] }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($strategicMetrics['stripe_health_score']['score'], 2) }}%
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            Charges: {{ number_format($strategicMetrics['stripe_health_score']['charges_enabled_rate'], 1) }}% | 
                            Payouts: {{ number_format($strategicMetrics['stripe_health_score']['payouts_enabled_rate'], 1) }}% | 
                            Onboarding: {{ number_format($strategicMetrics['stripe_health_score']['onboarding_complete_rate'], 1) }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Paiements --}}
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statistiques Paiements ({{ $month }})</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h6 class="text-muted">Réussis</h6>
                        <h4 class="text-success">{{ $dashboardMetrics['payments']['successful'] }}</h4>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Échoués</h6>
                        <h4 class="text-danger">{{ $dashboardMetrics['payments']['failed'] }}</h4>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted">Taux d'échec</h6>
                        <h4 class="{{ $dashboardMetrics['payments']['failure_rate'] > 10 ? 'text-danger' : ($dashboardMetrics['payments']['failure_rate'] > 5 ? 'text-warning' : 'text-success') }}">
                            {{ number_format($dashboardMetrics['payments']['failure_rate'], 2) }}%
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Créateurs à Risque</h5>
            </div>
            <div class="card-body">
                @if($riskStatistics['total_at_risk'] > 0)
                    <div class="alert alert-warning">
                        <strong>{{ $riskStatistics['total_at_risk'] }} créateur(s) à risque détecté(s)</strong>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <small class="text-muted">Critique</small>
                            <h5 class="text-danger">{{ $riskStatistics['by_level']['critical'] }}</h5>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Élevé</small>
                            <h5 class="text-warning">{{ $riskStatistics['by_level']['high'] }}</h5>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Moyen</small>
                            <h5 class="text-info">{{ $riskStatistics['by_level']['medium'] }}</h5>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success">
                        <strong>Aucun créateur à risque</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Derniers webhooks et incidents --}}
<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Derniers Webhooks Billing</h5>
            </div>
            <div class="card-body">
                @if(count($dashboardMetrics['webhooks']['recent']) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dashboardMetrics['webhooks']['recent'] as $webhook)
                                    <tr>
                                        <td><code>{{ $webhook->event_type ?? 'N/A' }}</code></td>
                                        <td>{{ $webhook->created_at ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $webhook->status === 'processed' ? 'success' : 'warning' }}">
                                                {{ $webhook->status ?? 'N/A' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Aucun webhook récent</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Derniers Incidents Stripe</h5>
            </div>
            <div class="card-body">
                @if(count($dashboardMetrics['stripe_incidents']) > 0)
                    <div class="list-group">
                        @foreach($dashboardMetrics['stripe_incidents'] as $incident)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $incident['creator_name'] ?? 'N/A' }}</h6>
                                        <small class="text-muted">{{ $incident['stripe_account_id'] ?? 'N/A' }}</small>
                                        <div class="mt-2">
                                            @foreach($incident['issues'] as $issue)
                                                <span class="badge bg-danger me-1">{{ $issue }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">Aucun incident détecté</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

