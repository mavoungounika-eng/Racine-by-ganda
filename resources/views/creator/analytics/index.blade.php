@extends('layouts.creator')

@section('title', 'Analytics - RACINE BY GANDA')
@section('page-title', 'Analytics')

@section('content')
<div class="creator-content-wrapper">
    {{-- Breadcrumb --}}
    <div class="creator-breadcrumb mb-3">
        <a href="{{ route('creator.dashboard') }}">Dashboard</a> / 
        <span>Analytics</span>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">Mes Analytics</h1>
            <p class="text-muted mb-0">Vue d'ensemble de vos performances de vente</p>
        </div>
        <div>
            <a href="{{ route('creator.analytics.sales') }}" class="btn btn-primary">
                <i class="fas fa-chart-line mr-2"></i>
                Détails ventes
            </a>
            <a href="{{ route('creator.analytics.index', ['refresh' => 1]) }}" class="btn btn-outline-secondary ml-2" title="Actualiser les données">
                <i class="fas fa-sync-alt"></i>
            </a>
        </div>
    </div>

    {{-- Filtres période --}}
    <div class="card-racine mb-4">
        <form method="GET" action="{{ route('creator.analytics.index') }}" class="d-flex align-items-center gap-3">
            <label class="mb-0 font-weight-bold">Période :</label>
            <select name="period" class="form-control form-control-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 derniers jours</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 derniers jours</option>
                <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Ce mois</option>
            </select>
        </form>
    </div>

    {{-- KPIs Principaux --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-success mb-2">
                    {{ number_format($stats['kpis']['revenue_total'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
                <div class="text-muted">Chiffre d'affaires</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-primary mb-2">
                    {{ number_format($stats['kpis']['orders_count'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted">Commandes payées</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-info mb-2">
                    @if($stats['kpis']['avg_order_value'] ?? null)
                        {{ number_format($stats['kpis']['avg_order_value'], 0, ',', ' ') }} FCFA
                    @else
                        -
                    @endif
                </div>
                <div class="text-muted">Panier moyen</div>
            </div>
        </div>
    </div>

    {{-- Top Produits --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-racine">
                <h3 class="h5 font-weight-bold mb-3">
                    <i class="fas fa-star mr-2 text-warning"></i>
                    Top 10 Produits
                </h3>
                @if(!empty($stats['top_products']))
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-right">Quantité vendue</th>
                                    <th class="text-right">CA généré</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['top_products'] as $product)
                                    <tr>
                                        <td>
                                            <strong>{{ $product['name'] }}</strong>
                                        </td>
                                        <td class="text-right">
                                            <span class="badge badge-primary">{{ $product['total_quantity'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <strong>{{ number_format($product['total_revenue'], 0, ',', ' ') }} FCFA</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Aucune vente pour cette période.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Évolution temporelle (simple) --}}
    @if(!empty($stats['timeline']['labels']))
        <div class="row">
            <div class="col-12">
                <div class="card-racine">
                    <h3 class="h5 font-weight-bold mb-3">
                        <i class="fas fa-chart-line mr-2 text-primary"></i>
                        Évolution des ventes
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-right">Commandes</th>
                                    <th class="text-right">CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['timeline']['labels'] as $index => $date)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                                        <td class="text-right">{{ $stats['timeline']['orders'][$index] ?? 0 }}</td>
                                        <td class="text-right">
                                            {{ number_format($stats['timeline']['revenue'][$index] ?? 0, 0, ',', ' ') }} FCFA
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

