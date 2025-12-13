@extends('layouts.creator')

@section('title', 'Statistiques de Ventes - RACINE BY GANDA')
@section('page-title', 'Statistiques de Ventes')

@section('content')
<div class="creator-content-wrapper">
    {{-- Breadcrumb --}}
    <div class="creator-breadcrumb mb-3">
        <a href="{{ route('creator.dashboard') }}">Dashboard</a> / 
        <a href="{{ route('creator.analytics.index') }}">Analytics</a> / 
        <span>Ventes</span>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">Statistiques de Ventes</h1>
            <p class="text-muted mb-0">Analyse détaillée de vos ventes et performances</p>
        </div>
        <div>
            <a href="{{ route('creator.analytics.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </a>
            <a href="{{ route('creator.analytics.sales', ['refresh' => 1]) }}" class="btn btn-outline-primary ml-2" title="Actualiser les données">
                <i class="fas fa-sync-alt mr-2"></i>
                Actualiser
            </a>
        </div>
    </div>

    {{-- Filtres période --}}
    <div class="card-racine mb-4">
        <form method="GET" action="{{ route('creator.analytics.sales') }}" class="d-flex align-items-center gap-3 flex-wrap">
            <label class="mb-0 font-weight-bold">Période :</label>
            <select name="period" class="form-control form-control-sm" style="width: auto;">
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 derniers jours</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 derniers jours</option>
                <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Ce mois</option>
                <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Personnalisée</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Appliquer</button>
        </form>
    </div>

    {{-- KPIs Détaillés --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-success mb-2">
                    {{ number_format($stats['kpis']['revenue_total'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
                <div class="text-muted small">Chiffre d'affaires total</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-primary mb-2">
                    {{ number_format($stats['kpis']['orders_count'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted small">Commandes payées</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-info mb-2">
                    @if($stats['kpis']['avg_order_value'] ?? null)
                        {{ number_format($stats['kpis']['avg_order_value'], 0, ',', ' ') }} FCFA
                    @else
                        -
                    @endif
                </div>
                <div class="text-muted small">Panier moyen</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-warning mb-2">
                    {{ count($stats['top_products'] ?? []) }}
                </div>
                <div class="text-muted small">Produits vendus</div>
            </div>
        </div>
    </div>

    {{-- Top Produits Détaillé --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-racine">
                <h3 class="h5 font-weight-bold mb-3">
                    <i class="fas fa-trophy mr-2 text-warning"></i>
                    Top Produits (par quantité vendue)
                </h3>
                @if(!empty($stats['top_products']))
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produit</th>
                                    <th class="text-right">Quantité vendue</th>
                                    <th class="text-right">CA généré</th>
                                    <th class="text-right">Prix unitaire moyen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['top_products'] as $index => $product)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $index < 3 ? 'warning' : 'secondary' }}">
                                                {{ $index + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $product['name'] }}</strong>
                                        </td>
                                        <td class="text-right">
                                            <span class="badge badge-primary badge-pill">{{ $product['total_quantity'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-success">{{ number_format($product['total_revenue'], 0, ',', ' ') }} FCFA</strong>
                                        </td>
                                        <td class="text-right">
                                            {{ number_format($product['total_revenue'] / $product['total_quantity'], 0, ',', ' ') }} FCFA
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Aucune vente pour cette période.</p>
                        <a href="{{ route('creator.products.index') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter des produits
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Évolution temporelle --}}
    @if(!empty($stats['timeline']['labels']))
        <div class="row">
            <div class="col-12">
                <div class="card-racine">
                    <h3 class="h5 font-weight-bold mb-3">
                        <i class="fas fa-chart-area mr-2 text-primary"></i>
                        Évolution Journalière
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-right">Commandes</th>
                                    <th class="text-right">Chiffre d'affaires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['timeline']['labels'] as $index => $date)
                                    <tr>
                                        <td>
                                            <strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong>
                                        </td>
                                        <td class="text-right">
                                            <span class="badge badge-info">{{ $stats['timeline']['orders'][$index] ?? 0 }}</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-success">
                                                {{ number_format($stats['timeline']['revenue'][$index] ?? 0, 0, ',', ' ') }} FCFA
                                            </strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td>Total</td>
                                    <td class="text-right">
                                        {{ array_sum($stats['timeline']['orders'] ?? []) }}
                                    </td>
                                    <td class="text-right text-success">
                                        {{ number_format(array_sum($stats['timeline']['revenue'] ?? []), 0, ',', ' ') }} FCFA
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

