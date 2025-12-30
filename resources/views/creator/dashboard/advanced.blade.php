@extends('layouts.creator')

@section('title', 'Tableau de Bord Avancé - RACINE BY GANDA')
@section('page-title', 'Tableau de bord')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
<style>
    .creator-hero {
        background: linear-gradient(135deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
        padding: 2.5rem 0;
        margin: -2rem -2rem 2rem -2rem;
        border-bottom: 2px solid rgba(237, 95, 30, 0.3);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border-left: 4px solid var(--racine-orange);
    }
    
    .stat-card-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--racine-black);
        margin: 0.5rem 0;
    }
    
    .chart-container {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }
</style>
@endpush

@section('content')
<div class="creator-dashboard">
    {{-- HERO --}}
    <div class="creator-hero">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div style="width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-orange);">
                    <span style="color: white; font-size: 2.75rem; font-weight: 700;">{{ strtoupper(substr($creatorProfile->brand_name ?? $user->name ?? 'C', 0, 1)) }}</span>
                </div>
                <div>
                    <h2 style="color: white; margin: 0 0 0.5rem 0; font-size: 1.75rem;">Bonjour, {{ $creatorProfile->brand_name ?? $user->name ?? 'Créateur' }}</h2>
                    <p style="color: rgba(255,255,255,0.7); margin: 0;">Plan : <strong style="color: white;">{{ $user->activePlan()->name ?? 'Gratuit' }}</strong></p>
                </div>
            </div>
            <a href="{{ route('creator.products.create') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%); color: white; border-radius: var(--radius-lg); text-decoration: none; font-weight: 600;">
                <i class="fas fa-plus"></i> Nouveau Produit
            </a>
        </div>
    </div>

    {{-- STATS ADVANCED --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size: 0.875rem; color: #8B7355; text-transform: uppercase; margin-bottom: 0.5rem;">Produits Publiés</div>
            <div class="stat-card-value">{{ $stats['products_count'] ?? 0 }}</div>
            <div style="font-size: 0.875rem; color: #8B7355;">{{ $stats['active_products_count'] ?? 0 }} actifs</div>
        </div>
        
        <div class="stat-card">
            <div style="font-size: 0.875rem; color: #8B7355; text-transform: uppercase; margin-bottom: 0.5rem;">Ventes Total</div>
            <div class="stat-card-value" style="font-size: 1.75rem;">
                {{ number_format($stats['total_sales'] ?? 0, 0, ',', ' ') }}<small style="font-size: 0.6em;"> FCFA</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div style="font-size: 0.875rem; color: #8B7355; text-transform: uppercase; margin-bottom: 0.5rem;">Revenus ce Mois</div>
            <div class="stat-card-value" style="font-size: 1.75rem;">
                {{ number_format($stats['monthly_sales'] ?? 0, 0, ',', ' ') }}<small style="font-size: 0.6em;"> FCFA</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div style="font-size: 0.875rem; color: #8B7355; text-transform: uppercase; margin-bottom: 0.5rem;">Commandes en Attente</div>
            <div class="stat-card-value">{{ $stats['pending_orders'] ?? 0 }}</div>
        </div>
    </div>

    {{-- CHART --}}
    @if(isset($salesData) && $user->hasCapability('can_view_analytics'))
    <div class="chart-container">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; color: var(--racine-black);">
            <i class="fas fa-chart-line" style="color: var(--racine-orange);"></i> Évolution des Ventes
        </h3>
        <canvas id="salesChart" style="max-height: 300px;"></canvas>
    </div>
    @endif

    {{-- RECENT ORDERS --}}
    @if(isset($recentOrders) && $recentOrders->count() > 0)
    <div style="background: white; border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #F8F6F3;">
            <h3 style="margin: 0; font-size: 1.5rem; color: var(--racine-black);">
                <i class="fas fa-shopping-bag" style="color: var(--racine-orange);"></i> Commandes Récentes
            </h3>
            <a href="{{ route('creator.orders.index') }}" style="color: #D4A574; text-decoration: none; font-weight: 500;">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #F8F6F3;">
                    <tr>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #8B7355; font-size: 0.875rem;">Commande</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #8B7355; font-size: 0.875rem;">Client</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #8B7355; font-size: 0.875rem;">Montant</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #8B7355; font-size: 0.875rem;">Statut</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #8B7355; font-size: 0.875rem;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders->take(5) as $order)
                    <tr style="border-bottom: 1px solid #F8F6F3;">
                        <td style="padding: 1rem;"><a href="{{ route('creator.orders.show', $order) }}" style="color: #D4A574; text-decoration: none; font-weight: 600;">#{{ $order->id }}</a></td>
                        <td style="padding: 1rem;">{{ $order->customer_name ?? ($order->user->name ?? 'N/A') }}</td>
                        <td style="padding: 1rem;"><strong>{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</strong></td>
                        <td style="padding: 1rem;"><span style="padding: 0.375rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: rgba(34, 197, 94, 0.1); color: #22c55e;">{{ ucfirst($order->status ?? 'En attente') }}</span></td>
                        <td style="padding: 1rem;">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if(isset($salesData) && $user->hasCapability('can_view_analytics'))
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($salesData['labels'] ?? []),
                datasets: [{
                    label: 'Ventes (FCFA)',
                    data: @json($salesData['data'] ?? []),
                    borderColor: '#ED5F1E',
                    backgroundColor: 'rgba(237, 95, 30, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
</script>
@endpush
@endif
@endsection

