@extends('layouts.admin')

@section('title', 'Statistiques - RACINE BY GANDA')
@section('page_title', 'Statistiques')
@section('page_subtitle', 'Analyses et rapports détaillés')
@section('breadcrumb', 'Statistiques')

@section('content')

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:18px;">
            <div class="card-body">
                <i class="fas fa-box fa-2x text-primary mb-2"></i>
                <div class="h3 mb-0">{{ $stats['total_products'] }}</div>
                <div class="small text-muted">Produits</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:18px;">
            <div class="card-body">
                <i class="fas fa-shopping-cart fa-2x text-success mb-2"></i>
                <div class="h3 mb-0">{{ $stats['total_orders'] }}</div>
                <div class="small text-muted">Commandes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:18px;">
            <div class="card-body">
                <i class="fas fa-users fa-2x text-info mb-2"></i>
                <div class="h3 mb-0">{{ $stats['total_users'] }}</div>
                <div class="small text-muted">Utilisateurs</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:18px;">
            <div class="card-body">
                <i class="fas fa-dollar-sign fa-2x text-warning mb-2"></i>
                <div class="h3 mb-0">{{ number_format($stats['total_revenue'] / 1000, 0) }}K</div>
                <div class="small text-muted">Revenus (FCFA)</div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-8 mb-3">
        <div class="card-racine">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
                <h5 class="mb-0" style="font-family: var(--font-heading); font-size:1.1rem; font-weight:700;">Ventes mensuelles (12 derniers mois)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Mois</th>
                            <th class="text-end">Montant</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($monthlySales as $sale)
                            <tr>
                                <td>{{ $sale['month'] }}</td>
                                <td class="text-end fw-bold">{{ number_format($sale['amount'], 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="card-racine">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
                <h5 class="mb-0" style="font-family: var(--font-heading); font-size:1.1rem; font-weight:700;">Top 10 Produits</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($topProducts as $product)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="small">{{ Str::limit($product->title, 30) }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $product->total_sold ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
