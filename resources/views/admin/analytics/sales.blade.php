@extends('layouts.admin')

@section('title', 'Ventes & CA - Analytics - RACINE BY GANDA')

@section('content')
<div class="admin-content-wrapper">
    {{-- Breadcrumb --}}
    <div class="admin-breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> / 
        <a href="{{ route('admin.analytics.index') }}">Analytics</a> / 
        <span>Ventes & CA</span>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">Ventes & Chiffres d'affaires</h1>
            <p class="text-muted mb-0">Analyse des performances commerciales</p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card-racine mb-4">
        <form method="GET" action="{{ route('admin.analytics.sales') }}" class="row align-items-end">
            <div class="col-md-3 mb-3">
                <label for="period" class="form-label small font-weight-bold">Période</label>
                <select name="period" id="period" class="form-control" onchange="this.form.submit()">
                    <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 derniers jours</option>
                    <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 derniers jours</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Ce mois</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Plage personnalisée</option>
                </select>
            </div>
            @if($period === 'custom')
            <div class="col-md-3 mb-3">
                <label for="start_date" class="form-label small font-weight-bold">Date de début</label>
                <input type="date" name="start_date" id="start_date" class="form-control" 
                       value="{{ request('start_date', now()->subDays(7)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="end_date" class="form-label small font-weight-bold">Date de fin</label>
                <input type="date" name="end_date" id="end_date" class="form-control" 
                       value="{{ request('end_date', now()->format('Y-m-d')) }}" required>
            </div>
            @endif
            <div class="col-md-3 mb-3">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter mr-2"></i>
                    Appliquer
                </button>
            </div>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-success mb-2">
                    {{ number_format($stats['kpis']['revenue_total'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
                <div class="text-muted">Chiffre d'affaires total</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-primary mb-2">
                    {{ number_format($stats['kpis']['orders_count'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted">Commandes payées</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-info mb-2">
                    {{ $stats['kpis']['avg_order_value'] !== null ? number_format($stats['kpis']['avg_order_value'], 0, ',', ' ') . ' FCFA' : 'N/A' }}
                </div>
                <div class="text-muted">Panier moyen</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h3 font-weight-bold text-warning mb-2">
                    {{ number_format($stats['kpis']['unique_customers'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted">Clients uniques</div>
            </div>
        </div>
    </div>

    {{-- Répartition par méthode de paiement --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card-racine">
                <h4 class="h6 font-weight-bold mb-3">
                    <i class="fas fa-credit-card mr-2 text-primary"></i>
                    Répartition par méthode de paiement
                </h4>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Méthode</th>
                                <th class="text-right">Commandes</th>
                                <th class="text-right">CA</th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['by_payment_method'] as $data)
                            <tr>
                                <td>
                                    @if($data['method'] === 'card')
                                        <i class="fas fa-credit-card mr-2"></i> Carte bancaire
                                    @elseif($data['method'] === 'mobile_money')
                                        <i class="fas fa-mobile-alt mr-2"></i> Mobile Money
                                    @elseif($data['method'] === 'cash_on_delivery')
                                        <i class="fas fa-money-bill-wave mr-2"></i> Paiement à la livraison
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $data['method'])) }}
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($data['orders_count'], 0, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($data['revenue'], 0, ',', ' ') }} FCFA</td>
                                <td class="text-right">
                                    <span class="badge badge-primary">{{ number_format($data['revenue_share'], 1) }}%</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Aucune donnée</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card-racine">
                <h4 class="h6 font-weight-bold mb-3">
                    <i class="fas fa-chart-pie mr-2 text-primary"></i>
                    Évolution journalière
                </h4>
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
                            @if(!empty($stats['timeline']['labels']))
                                @foreach(array_slice($stats['timeline']['labels'], -7) as $index => $date)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                                    <td class="text-right">{{ number_format($stats['timeline']['orders'][$index] ?? 0, 0, ',', ' ') }}</td>
                                    <td class="text-right">{{ number_format($stats['timeline']['revenue'][$index] ?? 0, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Aucune donnée</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top produits --}}
    <div class="card-racine">
        <h4 class="h6 font-weight-bold mb-3">
            <i class="fas fa-star mr-2 text-warning"></i>
            Top 10 produits vendus
        </h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="text-right">Quantité vendue</th>
                        <th class="text-right">Chiffre d'affaires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['top_products'] as $product)
                    <tr>
                        <td>
                            <strong>{{ $product['name'] }}</strong>
                            <br><small class="text-muted">#{{ $product['product_id'] }}</small>
                        </td>
                        <td class="text-right">
                            <span class="badge badge-primary">{{ number_format($product['total_quantity'], 0, ',', ' ') }}</span>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format($product['total_revenue'], 0, ',', ' ') }} FCFA</strong>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Aucun produit vendu sur cette période</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

