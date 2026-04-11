@extends('layouts.admin-master')

@section('title', 'ERP - Tableau de Bord')
@section('page-title', 'ERP - Tableau de Bord')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h1 class="h2 mb-1">📦 Module ERP</h1>
                        <p class="text-muted mb-0">Gestion des stocks, fournisseurs et matières premières</p>
                    </div>
                    <div>
                        <span class="badge bg-primary py-2 px-3">v1.1</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row 1 --}}
        <div class="row mb-4">
            {{-- Valorisation Stock (Produits) --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card bg-primary text-white">
                    <div class="card-body">
                        <div class="kpi-icon">💰</div>
                        <div class="kpi-value">{{ number_format($stats['stock_value_global'], 0, ',', ' ') }} <small style="font-size: 1rem">XAF</small></div>
                        <div class="kpi-label">Valorisation Stock (Produits)</div>
                    </div>
                </div>
            </div>

            {{-- Achats du Mois --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card">
                    <div class="card-body">
                        <div class="kpi-icon">🛒</div>
                        <div class="kpi-value">{{ $stats['purchases_month_count'] }}</div>
                        <div class="kpi-label">Commandes ce mois</div>
                        <small class="text-muted">{{ number_format($stats['purchases_month_sum'], 0, ',', ' ') }} XAF</small>
                    </div>
                </div>
            </div>

            {{-- Flux Entrée Aujourd'hui --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card">
                    <div class="card-body">
                        <div class="kpi-icon text-success">⬇️</div>
                        <div class="kpi-value text-success">+{{ $stats['flow_today_in'] }}</div>
                        <div class="kpi-label">Entrées Stock (Auj.)</div>
                    </div>
                </div>
            </div>

            {{-- Flux Sortie Aujourd'hui --}}
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100 kpi-card">
                    <div class="card-body">
                        <div class="kpi-icon text-danger">⬆️</div>
                        <div class="kpi-value text-danger">-{{ $stats['flow_today_out'] }}</div>
                        <div class="kpi-label">Sorties Stock (Auj.)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row 2 (Alertes) --}}
        <div class="row mb-4">
            <div class="col-md-4 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Produits Total</p>
                                <h2 class="mb-0">{{ $stats['products_total'] }}</h2>
                            </div>
                            <span class="h1 text-primary opacity-50">👗</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100 bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="small mb-1">Stock Faible (< 5)</p>
                                <h2 class="mb-0">{{ $stats['products_low_stock'] }}</h2>
                            </div>
                            <span class="h1 opacity-50">⚠️</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-12 mb-3">
                <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="small mb-1 opacity-75">Rupture de Stock</p>
                                <h2 class="mb-0">{{ $stats['products_out_of_stock'] }}</h2>
                            </div>
                            <span class="h1 opacity-50">🚫</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Produits Stock Faible --}}
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">⚠️ Alertes Stock</h5>
                        <a href="{{ route('erp.stocks.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body p-0">
                        @if($low_stock_products->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0">Produit</th>
                                            <th class="border-0">Stock</th>
                                            <th class="border-0">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($low_stock_products as $product)
                                        <tr>
                                            <td class="align-middle">
                                                <strong>{{ Str::limit($product->title, 25) }}</strong>
                                            </td>
                                            <td class="align-middle">
                                                <span class="font-weight-bold {{ $product->stock <= 0 ? 'text-danger' : 'text-warning' }}">
                                                    {{ $product->stock }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                @if($product->stock <= 0)
                                                    <span class="badge bg-danger">Rupture</span>
                                                @elseif($product->stock < 5)
                                                    <span class="badge bg-warning">Critique</span>
                                                @else
                                                    <span class="badge bg-info">Faible</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <span class="display-1">✅</span>
                                <h5 class="text-muted mt-3">Tous les stocks sont OK !</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions Rapides --}}
            <div class="col-lg-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">📊 Rapports & Exports</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.stock-valuation') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-coins me-1"></i> Valorisation Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.purchases') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-shopping-cart me-1"></i> Rapport Achats
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.stock-movements') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-exchange-alt me-1"></i> Mouvements Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.replenishment-suggestions') }}" class="btn btn-outline-warning btn-sm w-100">
                                    <i class="fas fa-lightbulb me-1"></i> Suggestions Réappro
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        {{-- Section Rapports & Actions Rapides --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">📊 Rapports & Exports</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.stock-valuation') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-coins me-1"></i> Valorisation Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.purchases') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-shopping-cart me-1"></i> Rapport Achats
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.stock-movements') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-exchange-alt me-1"></i> Mouvements Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.replenishment-suggestions') }}" class="btn btn-outline-warning btn-sm w-100">
                                    <i class="fas fa-lightbulb me-1"></i> Suggestions Réappro
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Top Matières & Achats Récents --}}
            <div class="col-lg-6 mb-4">
                {{-- Top Matières --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">🧵 Top Matières (Achat)</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($top_materials->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($top_materials as $item)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $item->purchasable->name ?? 'Inconnu' }}
                                        <span class="badge bg-primary rounded-pill">{{ $item->total_qty }} unités</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-4 text-center text-muted">Pas assez de données</div>
                        @endif
                    </div>
                </div>

                {{-- Achats Récents --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">🛒 Derniers Achats</h5>
                        <a href="{{ route('erp.purchases.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body p-0">
                        @if($recent_purchases->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recent_purchases as $purchase)
                                    <a href="{{ route('erp.purchases.show', $purchase) }}" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $purchase->supplier->name }}</h6>
                                            <small>{{ $purchase->purchase_date->format('d/m/Y') }}</small>
                                        </div>
                                        <p class="mb-1 text-muted small">Ref: {{ $purchase->reference }}</p>
                                        <small class="font-weight-bold">{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</small>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">Aucun achat récent</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Rapports & Actions Rapides --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">📊 Rapports & Exports</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.stock-valuation') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-coins me-1"></i> Valorisation Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.purchases') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-shopping-cart me-1"></i> Rapport Achats
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.stock-movements') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-exchange-alt me-1"></i> Mouvements Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('erp.reports.replenishment-suggestions') }}" class="btn btn-outline-warning btn-sm w-100">
                                    <i class="fas fa-lightbulb me-1"></i> Suggestions Réappro
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection
