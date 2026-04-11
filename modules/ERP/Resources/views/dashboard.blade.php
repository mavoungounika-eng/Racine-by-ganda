@extends('layouts.admin-master')

@section('title', 'ERP - Tableau de Bord')
@section('page-title', 'ERP - Tableau de Bord')
@section('page-subtitle', 'Gestion des stocks, fournisseurs et matières premières')

@section('content')

{{-- En-tête --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-warehouse text-racine-orange me-2"></i>
            Module ERP
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Gestion des stocks, fournisseurs et matières premières
        </p>
    </div>
    <span class="badge badge-racine-orange">v1.1</span>
</div>

{{-- Statistiques principales --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Valorisation Stock',
            'value' => number_format($stats['stock_value_global'], 0, ',', ' ') . ' FCFA',
            'icon' => 'fas fa-coins',
            'color' => 'primary',
            'subtitle' => 'Valeur totale des produits en stock'
        ])
    </div>

    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Achats ce mois',
            'value' => $stats['purchases_month_count'],
            'icon' => 'fas fa-shopping-cart',
            'color' => 'info',
            'subtitle' => number_format($stats['purchases_month_sum'], 0, ',', ' ') . ' FCFA'
        ])
    </div>

    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Entrées Stock (Auj.)',
            'value' => '+' . $stats['flow_today_in'],
            'icon' => 'fas fa-arrow-down',
            'color' => 'success',
            'subtitle' => 'Mouvements entrants aujourd\'hui'
        ])
    </div>

    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Sorties Stock (Auj.)',
            'value' => '-' . $stats['flow_today_out'],
            'icon' => 'fas fa-arrow-up',
            'color' => 'danger',
            'subtitle' => 'Mouvements sortants aujourd\'hui'
        ])
    </div>
</div>

{{-- Alertes Stock --}}
<div class="row g-4 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="card card-racine h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase small text-muted mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">
                            Produits Total
                        </div>
                        <div class="h3 mb-0 text-racine-black" style="font-weight: 700; font-size: 1.75rem;">
                            {{ $stats['products_total'] }}
                        </div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 56px; height: 56px; background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <i class="fas fa-box fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card card-racine h-100 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase small text-muted mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">
                            Stock Faible (< 5)
                        </div>
                        <div class="h3 mb-0 text-warning" style="font-weight: 700; font-size: 1.75rem;">
                            {{ $stats['products_low_stock'] }}
                        </div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 56px; height: 56px; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card card-racine h-100 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase small text-muted mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">
                            Rupture de Stock
                        </div>
                        <div class="h3 mb-0 text-danger" style="font-weight: 700; font-size: 1.75rem;">
                            {{ $stats['products_out_of_stock'] }}
                        </div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 56px; height: 56px; background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alertes Stock et Actions Rapides --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Alertes Stock
                </h5>
                <a href="{{ route('erp.stocks.index') }}" class="btn btn-sm btn-outline-racine-orange">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @if($low_stock_products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Produit</th>
                                    <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Stock</th>
                                    <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($low_stock_products as $product)
                                <tr>
                                    <td style="padding: 1.25rem 1rem;">
                                        <div class="fw-semibold text-racine-black">{{ Str::limit($product->title, 30) }}</div>
                                    </td>
                                    <td style="padding: 1.25rem 1rem;">
                                        <span class="fw-bold {{ $product->stock <= 0 ? 'text-danger' : 'text-warning' }}">
                                            {{ $product->stock }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem 1rem;">
                                        @if($product->stock <= 0)
                                            <span class="badge bg-danger rounded-pill">
                                                <i class="fas fa-times-circle me-1"></i>Rupture
                                            </span>
                                        @elseif($product->stock < 5)
                                            <span class="badge bg-warning text-dark rounded-pill">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Critique
                                            </span>
                                        @else
                                            <span class="badge bg-info rounded-pill">
                                                <i class="fas fa-info-circle me-1"></i>Faible
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 opacity-50 text-success"></i>
                        <p class="mb-0">Tous les stocks sont OK !</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions Rapides --}}
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-bolt text-racine-orange me-2"></i>
                    Actions Rapides
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('erp.stocks.index') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-warehouse me-2"></i>
                            Gérer les Stocks
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-truck me-2"></i>
                            Fournisseurs
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('erp.materials.index') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-cube me-2"></i>
                            Matières Premières
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('erp.purchases.index') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Achats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Rapports & Exports --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-bar text-racine-orange me-2"></i>
                    Rapports & Exports
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <a href="{{ route('erp.reports.stock-valuation') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-coins me-2"></i>
                            Valorisation Stock
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="{{ route('erp.reports.purchases') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Rapport Achats
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="{{ route('erp.reports.stock-movements') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-exchange-alt me-2"></i>
                            Mouvements Stock
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="{{ route('erp.reports.replenishment-suggestions') }}" class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-lightbulb me-2"></i>
                            Suggestions Réappro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Matières et Achats Récents --}}
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-cube text-racine-orange me-2"></i>
                    Top Matières (Achat)
                </h5>
            </div>
            <div class="card-body p-0">
                @if($top_materials->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($top_materials as $item)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-racine-black">
                                        {{ $item->purchasable->name ?? 'Inconnu' }}
                                    </div>
                                </div>
                                <span class="badge bg-primary rounded-pill">
                                    <i class="fas fa-boxes me-1"></i>
                                    {{ $item->total_qty }} unités
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-cube fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Pas assez de données</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-shopping-cart text-racine-orange me-2"></i>
                    Derniers Achats
                </h5>
                <a href="{{ route('erp.purchases.index') }}" class="btn btn-sm btn-outline-racine-orange">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @if($recent_purchases->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recent_purchases as $purchase)
                            <a href="{{ route('erp.purchases.show', $purchase) }}" class="list-group-item list-group-item-action py-3">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="flex-fill">
                                        <div class="fw-bold text-racine-black mb-1">
                                            {{ $purchase->supplier->name }}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-hashtag me-1"></i>
                                            Ref: {{ $purchase->reference }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-racine-orange mb-1">
                                            {{ number_format($purchase->total_amount, 0, ',', ' ') }} FCFA
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $purchase->purchase_date->format('d/m/Y') }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Aucun achat récent</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

