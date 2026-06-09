@extends('layouts.creator')

@section('title', 'Tableau de bord Financier')

@section('content')
<div class="container-fluid py-4">
    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Tableau de bord Financier</h1>
            <p class="text-muted mb-0">Vue d'ensemble de vos revenus et ventes</p>
        </div>
        <div class="btn-group" role="group">
            <a href="?period=week" class="btn btn-sm {{ $period === 'week' ? 'btn-primary' : 'btn-outline-primary' }}">Semaine</a>
            <a href="?period=month" class="btn btn-sm {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">Mois</a>
            <a href="?period=year" class="btn btn-sm {{ $period === 'year' ? 'btn-primary' : 'btn-outline-primary' }}">Année</a>
        </div>
    </div>

    {{-- Statistiques période --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Revenu net créateur</div>
                    <div class="h4 mb-0">{{ number_format($stats['creator_revenue'], 0, ',', ' ') }} XAF</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Ventes</div>
                    <div class="h4 mb-0">{{ $stats['sales_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Frais service (5%)</div>
                    <div class="h4 mb-0">{{ number_format($stats['service_fee'], 0, ',', ' ') }} XAF</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">TVA (18%)</div>
                    <div class="h4 mb-0">{{ number_format($stats['vat'], 0, ',', ' ') }} XAF</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiques globales --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="text-muted small">Total HT (toutes périodes)</div>
                    <div class="h5 mb-0">{{ number_format($stats['all_time_product_ht'], 0, ',', ' ') }} XAF</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="text-muted small">Frais cumulés</div>
                    <div class="h5 mb-0">{{ number_format($stats['all_time_service_fee'], 0, ',', ' ') }} XAF</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="text-muted small">TVA cumulée</div>
                    <div class="h5 mb-0">{{ number_format($stats['all_time_vat'], 0, ',', ' ') }} XAF</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Compte Stripe --}}
    @if($stripeAccount)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fab fa-stripe me-2"></i>Compte Stripe Connect</h5>
        </div>
        <div class="card-body">
            <span class="badge {{ $stripeAccount->charges_enabled ? 'bg-success' : 'bg-warning' }}">
                {{ $stripeAccount->charges_enabled ? 'Actif' : 'En attente de vérification' }}
            </span>
        </div>
    </div>
    @endif

    {{-- Graphique ventes --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Évolution des ventes</h5>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="80"></canvas>
        </div>
    </div>

    {{-- Dernières commandes --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Dernières commandes payées</h5>
        </div>
        <div class="card-body p-0">
            @if($recentOrders->isEmpty())
                <div class="text-center py-4 text-muted">Aucune commande payée pour le moment.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Commande</th>
                            <th>Date</th>
                            <th class="text-end">Prix HT</th>
                            <th class="text-end">Frais</th>
                            <th class="text-end">TVA</th>
                            <th class="text-end">Total TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td><code>#{{ $order->order_number ?? $order->id }}</code></td>
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">{{ number_format($order->creator_product_ht, 0, ',', ' ') }} XAF</td>
                            <td class="text-end">{{ number_format($order->creator_service_fee, 0, ',', ' ') }} XAF</td>
                            <td class="text-end">{{ number_format($order->creator_vat, 0, ',', ' ') }} XAF</td>
                            <td class="text-end fw-bold">{{ number_format($order->creator_total_ttc, 0, ',', ' ') }} XAF</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script nonce="{{ csp_nonce() }}">
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(collect($salesChartData)->pluck('label')) !!},
                datasets: [{
                    label: 'Revenus (XAF)',
                    data: {!! json_encode(collect($salesChartData)->pluck('value')) !!},
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
</script>
@endpush
@endsection
