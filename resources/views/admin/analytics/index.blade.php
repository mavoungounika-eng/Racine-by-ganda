@extends('layouts.admin')

@section('title', 'Dashboard Analytics - RACINE BY GANDA')

@section('content')
<div class="admin-content-wrapper">
    {{-- Breadcrumb --}}
    <div class="admin-breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> / 
        <span>Analytics</span>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">Dashboard Analytics</h1>
            <p class="text-muted mb-0">Vue d'ensemble des performances et du tunnel d'achat</p>
        </div>
        <div>
            <a href="{{ route('admin.analytics.funnel') }}" class="btn btn-primary mr-2">
                <i class="fas fa-funnel-dollar mr-2"></i>
                Funnel d'achat
            </a>
            <a href="{{ route('admin.analytics.sales') }}" class="btn btn-outline-primary">
                <i class="fas fa-dollar-sign mr-2"></i>
                Ventes & CA
            </a>
        </div>
    </div>

    {{-- KPIs Funnel (7 derniers jours) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-racine">
                <h3 class="h5 font-weight-bold mb-3">
                    <i class="fas fa-funnel-dollar mr-2 text-primary"></i>
                    Funnel d'achat (7 derniers jours)
                </h3>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h4 font-weight-bold text-primary mb-1">
                                {{ number_format($funnelStats['counts']['product_added_to_cart'] ?? 0, 0, ',', ' ') }}
                            </div>
                            <div class="text-muted small">Produits ajoutés</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h4 font-weight-bold text-info mb-1">
                                {{ number_format($funnelStats['counts']['checkout_started'] ?? 0, 0, ',', ' ') }}
                            </div>
                            <div class="text-muted small">Checkouts démarrés</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h4 font-weight-bold text-warning mb-1">
                                {{ number_format($funnelStats['counts']['order_placed'] ?? 0, 0, ',', ' ') }}
                            </div>
                            <div class="text-muted small">Commandes créées</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="h4 font-weight-bold text-success mb-1">
                                {{ number_format($funnelStats['counts']['payment_completed'] ?? 0, 0, ',', ' ') }}
                            </div>
                            <div class="text-muted small">Paiements complétés</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.analytics.funnel') }}" class="btn btn-sm btn-outline-primary">
                        Voir le détail du funnel <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs Ventes (7 derniers jours) --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-success mb-2">
                    {{ number_format($salesStats['kpis']['revenue_total'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
                <div class="text-muted">Chiffre d'affaires</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-primary mb-2">
                    {{ number_format($salesStats['kpis']['orders_count'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted">Commandes payées</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-info mb-2">
                    {{ $salesStats['kpis']['avg_order_value'] !== null ? number_format($salesStats['kpis']['avg_order_value'], 0, ',', ' ') . ' FCFA' : 'N/A' }}
                </div>
                <div class="text-muted">Panier moyen</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-warning mb-2">
                    {{ number_format($salesStats['kpis']['unique_customers'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted">Clients uniques</div>
            </div>
        </div>
    </div>

    {{-- Liens rapides --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card-racine">
                <h4 class="h6 font-weight-bold mb-3">
                    <i class="fas fa-funnel-dollar mr-2 text-primary"></i>
                    Funnel d'achat
                </h4>
                <p class="text-muted mb-3">Analysez où les utilisateurs abandonnent dans le tunnel d'achat et les taux de conversion.</p>
                <a href="{{ route('admin.analytics.funnel') }}" class="btn btn-primary btn-sm">
                    Accéder au dashboard Funnel <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card-racine">
                <h4 class="h6 font-weight-bold mb-3">
                    <i class="fas fa-dollar-sign mr-2 text-success"></i>
                    Ventes & Chiffres d'affaires
                </h4>
                <p class="text-muted mb-3">Suivez les performances commerciales, le CA, les top produits et l'évolution dans le temps.</p>
                <a href="{{ route('admin.analytics.sales') }}" class="btn btn-success btn-sm">
                    Accéder au dashboard Ventes <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

