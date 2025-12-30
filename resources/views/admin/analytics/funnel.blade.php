@extends('layouts.admin')

@section('title', 'Funnel d\'achat - Analytics - RACINE BY GANDA')

@section('content')
<div class="admin-content-wrapper">
    {{-- Breadcrumb --}}
    <div class="admin-breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> / 
        <a href="{{ route('admin.analytics.index') }}">Analytics</a> / 
        <span>Funnel d'achat</span>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">Funnel d'achat</h1>
            <p class="text-muted mb-0">Analyse des conversions et des points d'abandon</p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card-racine mb-4">
        <form method="GET" action="{{ route('admin.analytics.funnel') }}" class="row align-items-end">
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
                <label for="payment_method" class="form-label small font-weight-bold">Méthode de paiement</label>
                <select name="payment_method" id="payment_method" class="form-control" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <option value="card" {{ $paymentMethod === 'card' ? 'selected' : '' }}>Carte bancaire</option>
                    <option value="mobile_money" {{ $paymentMethod === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                    <option value="cash_on_delivery" {{ $paymentMethod === 'cash_on_delivery' ? 'selected' : '' }}>Paiement à la livraison</option>
                </select>
            </div>
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
                <div class="h4 font-weight-bold text-primary mb-2">
                    {{ number_format($stats['counts']['product_added_to_cart'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted small mb-1">Produits ajoutés au panier</div>
                <div class="badge badge-primary">Étape 1</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-info mb-2">
                    {{ number_format($stats['counts']['checkout_started'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted small mb-1">Checkouts démarrés</div>
                <div class="badge badge-info">Étape 2</div>
                @if(isset($stats['conversion_rates']['cart_to_checkout']) && $stats['conversion_rates']['cart_to_checkout'] !== null)
                <div class="mt-2">
                    <small class="text-muted">
                        Taux: {{ number_format($stats['conversion_rates']['cart_to_checkout'], 1) }}%
                    </small>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-warning mb-2">
                    {{ number_format($stats['counts']['order_placed'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted small mb-1">Commandes créées</div>
                <div class="badge badge-warning">Étape 3</div>
                @if(isset($stats['conversion_rates']['checkout_to_order']) && $stats['conversion_rates']['checkout_to_order'] !== null)
                <div class="mt-2">
                    <small class="text-muted">
                        Taux: {{ number_format($stats['conversion_rates']['checkout_to_order'], 1) }}%
                    </small>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-racine text-center">
                <div class="h4 font-weight-bold text-success mb-2">
                    {{ number_format($stats['counts']['payment_completed'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="text-muted small mb-1">Paiements complétés</div>
                <div class="badge badge-success">Étape 4</div>
                @if(isset($stats['conversion_rates']['order_to_payment']) && $stats['conversion_rates']['order_to_payment'] !== null)
                <div class="mt-2">
                    <small class="text-muted">
                        Taux: {{ number_format($stats['conversion_rates']['order_to_payment'], 1) }}%
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Taux de conversion global --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card-racine">
                <h4 class="h6 font-weight-bold mb-3">
                    <i class="fas fa-percentage mr-2 text-primary"></i>
                    Taux de conversion
                </h4>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Panier → Checkout</span>
                        <strong class="text-info">
                            {{ $stats['conversion_rates']['cart_to_checkout'] !== null ? number_format($stats['conversion_rates']['cart_to_checkout'], 1) . '%' : 'N/A' }}
                        </strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Checkout → Commande</span>
                        <strong class="text-warning">
                            {{ $stats['conversion_rates']['checkout_to_order'] !== null ? number_format($stats['conversion_rates']['checkout_to_order'], 1) . '%' : 'N/A' }}
                        </strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Commande → Paiement</span>
                        <strong class="text-success">
                            {{ $stats['conversion_rates']['order_to_payment'] !== null ? number_format($stats['conversion_rates']['order_to_payment'], 1) . '%' : 'N/A' }}
                        </strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                        <span class="font-weight-bold">Taux global</span>
                        <strong class="text-primary h5 mb-0">
                            {{ $stats['conversion_rates']['global_cart_to_payment'] !== null ? number_format($stats['conversion_rates']['global_cart_to_payment'], 1) . '%' : 'N/A' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card-racine">
                <h4 class="h6 font-weight-bold mb-3">
                    <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                    Échecs
                </h4>
                <div class="text-center py-4">
                    <div class="h3 font-weight-bold text-danger mb-2">
                        {{ number_format($stats['counts']['payment_failed'] ?? 0, 0, ',', ' ') }}
                    </div>
                    <div class="text-muted">Paiements échoués</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Évolution dans le temps (table simple pour l'instant) --}}
    <div class="card-racine">
        <h4 class="h6 font-weight-bold mb-3">
            <i class="fas fa-chart-line mr-2 text-primary"></i>
            Évolution jour par jour
        </h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Produits ajoutés</th>
                        <th class="text-right">Checkouts</th>
                        <th class="text-right">Commandes</th>
                        <th class="text-right">Paiements</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($stats['timeline']['labels']))
                        @foreach($stats['timeline']['labels'] as $index => $date)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                            <td class="text-right">{{ number_format($stats['timeline']['data']['product_added_to_cart'][$index] ?? 0, 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($stats['timeline']['data']['checkout_started'][$index] ?? 0, 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($stats['timeline']['data']['order_placed'][$index] ?? 0, 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($stats['timeline']['data']['payment_completed'][$index] ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Aucune donnée pour cette période</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

