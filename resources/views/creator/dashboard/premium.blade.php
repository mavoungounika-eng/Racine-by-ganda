@extends('layouts.creator')

@section('title', 'Tableau de Bord Premium - RACINE BY GANDA')
@section('page-title', 'Tableau de bord Premium')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
<style>
    .creator-hero {
        background: linear-gradient(135deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
        padding: 3rem 0;
        margin: -2rem -2rem 2rem -2rem;
        border-bottom: 3px solid var(--racine-orange);
        position: relative;
        overflow: hidden;
    }
    
    .creator-hero::before {
        content: '⭐';
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 4rem;
        opacity: 0.1;
    }
    
    .premium-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.875rem;
        box-shadow: var(--shadow-orange);
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
        border: 2px solid transparent;
        background-clip: padding-box;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--racine-orange), var(--racine-yellow));
    }
    
    .stat-card-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--racine-black);
        margin: 0.5rem 0;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .chart-container {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .premium-features {
        background: linear-gradient(135deg, #FFF8F0 0%, #FFFBF5 100%);
        border: 2px solid var(--racine-orange);
        border-radius: var(--radius-xl);
        padding: 2rem;
    }
</style>
@endpush

@section('content')
<div class="creator-dashboard">
    {{-- HERO PREMIUM --}}
    <div class="creator-hero">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem; position: relative; z-index: 1;">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-orange); border: 3px solid rgba(237, 95, 30, 0.3);">
                    <span style="color: white; font-size: 3rem; font-weight: 700;">{{ strtoupper(substr($creatorProfile->brand_name ?? $user->name ?? 'C', 0, 1)) }}</span>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                        <h2 style="color: white; margin: 0; font-size: 2rem;">Bonjour, {{ $creatorProfile->brand_name ?? $user->name ?? 'Créateur' }}</h2>
                        <span class="premium-badge">⭐ Premium</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 1rem;">Accès complet à toutes les fonctionnalités</p>
                </div>
            </div>
            <a href="{{ route('creator.products.create') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%); color: white; border-radius: var(--radius-lg); text-decoration: none; font-weight: 600; box-shadow: var(--shadow-orange); transition: var(--transition-fast);">
                <i class="fas fa-plus"></i> Nouveau Produit
            </a>
        </div>
    </div>

    {{-- STATS PREMIUM --}}
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

    {{-- DASHBOARD GRID --}}
    <div class="dashboard-grid">
        {{-- CHART --}}
        @if(isset($salesData))
        <div class="chart-container">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; color: var(--racine-black);">
                <i class="fas fa-chart-line" style="color: var(--racine-orange);"></i> Évolution des Ventes (12 mois)
            </h3>
            <canvas id="salesChart" style="max-height: 300px;"></canvas>
        </div>
        @endif

        {{-- PREMIUM FEATURES --}}
        <div class="premium-features">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: var(--racine-black);">
                <i class="fas fa-star" style="color: var(--racine-orange);"></i> Fonctionnalités Premium
            </h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(237, 95, 30, 0.2); display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle" style="color: #22c55e;"></i>
                    <span>Produits illimités</span>
                </li>
                <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(237, 95, 30, 0.2); display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle" style="color: #22c55e;"></i>
                    <span>Analytics avancées</span>
                </li>
                <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(237, 95, 30, 0.2); display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle" style="color: #22c55e;"></i>
                    <span>Export de données</span>
                </li>
                <li style="padding: 0.75rem 0; border-bottom: 1px solid rgba(237, 95, 30, 0.2); display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle" style="color: #22c55e;"></i>
                    <span>Accès API</span>
                </li>
                <li style="padding: 0.75rem 0; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle" style="color: #22c55e;"></i>
                    <span>Support dédié</span>
                </li>
            </ul>
        </div>
    </div>

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
                    @foreach($recentOrders->take(10) as $order)
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

@if(isset($salesData))
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
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }
</script>
@endpush
@endif
@endsection

