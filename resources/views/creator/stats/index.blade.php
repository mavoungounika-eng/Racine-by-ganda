@extends('layouts.creator')

@section('title', 'Statistiques & Performances - RACINE BY GANDA')
@section('page-title', 'Statistiques & Performances')

@push('styles')
<style>
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
        transition: all 0.3s ease;
    }
    
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
        border-color: rgba(212, 165, 116, 0.2);
    }
    
    .stat-card-premium {
        background: linear-gradient(135deg, white 0%, #faf8f5 100%);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(212, 165, 116, 0.15);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--stat-color-1), var(--stat-color-2));
    }
    
    .stat-card-premium:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .premium-select {
        background: white;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #2C1810;
        transition: all 0.3s;
    }
    
    .premium-select:focus {
        outline: none;
        border-color: #D4A574;
        box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.1);
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 1rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Filtre période --}}
    <div class="premium-card mb-8">
        <form method="GET" class="flex items-center gap-4 flex-wrap">
            <label class="text-sm font-semibold text-[#2C1810]">Période:</label>
            <select name="period" 
                    onchange="this.form.submit()"
                    class="premium-select">
                <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>7 derniers jours</option>
                <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>30 derniers jours</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Ce mois-ci</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Cette année</option>
            </select>
        </form>
    </div>

    {{-- Cartes récapitulatives avec évolution --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- CA Brut --}}
        <div class="stat-card-premium" style="--stat-color-1: #ED5F1E; --stat-color-2: #FFB800;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Chiffre d'affaires</p>
                    <p class="text-4xl font-bold text-[#ED5F1E]" style="font-family: 'Playfair Display', serif;">{{ number_format($summary['current']['gross'], 0, ',', ' ') }}</p>
                    <p class="text-sm text-[#8B7355] mt-1">FCFA</p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-[#ED5F1E] to-[#FFB800] flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-line text-white text-3xl"></i>
                </div>
            </div>
            @if($summary['evolution']['gross_percent'] != 0)
                <div class="flex items-center gap-2 pt-2 border-t border-[#E5DDD3]">
                    @if($summary['evolution']['gross_percent'] > 0)
                        <i class="fas fa-arrow-up text-[#22C55E]"></i>
                        <span class="text-sm text-[#22C55E] font-semibold">+{{ abs($summary['evolution']['gross_percent']) }}%</span>
                    @else
                        <i class="fas fa-arrow-down text-[#EF4444]"></i>
                        <span class="text-sm text-[#EF4444] font-semibold">{{ $summary['evolution']['gross_percent'] }}%</span>
                    @endif
                    <span class="text-xs text-[#8B7355]">vs période précédente</span>
                </div>
            @endif
        </div>

        {{-- Commandes --}}
        <div class="stat-card-premium" style="--stat-color-1: #3B82F6; --stat-color-2: #2563EB;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Commandes</p>
                    <p class="text-4xl font-bold text-[#3B82F6]" style="font-family: 'Playfair Display', serif;">{{ $summary['current']['orders_count'] }}</p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-[#3B82F6] to-[#2563EB] flex items-center justify-center shadow-lg">
                    <i class="fas fa-shopping-bag text-white text-3xl"></i>
                </div>
            </div>
            @if($summary['evolution']['orders_percent'] != 0)
                <div class="flex items-center gap-2 pt-2 border-t border-[#E5DDD3]">
                    @if($summary['evolution']['orders_percent'] > 0)
                        <i class="fas fa-arrow-up text-[#22C55E]"></i>
                        <span class="text-sm text-[#22C55E] font-semibold">+{{ abs($summary['evolution']['orders_percent']) }}%</span>
                    @else
                        <i class="fas fa-arrow-down text-[#EF4444]"></i>
                        <span class="text-sm text-[#EF4444] font-semibold">{{ $summary['evolution']['orders_percent'] }}%</span>
                    @endif
                    <span class="text-xs text-[#8B7355]">vs période précédente</span>
                </div>
            @endif
        </div>

        {{-- Produits vendus --}}
        <div class="stat-card-premium" style="--stat-color-1: #22C55E; --stat-color-2: #16A34A;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Produits vendus</p>
                    <p class="text-4xl font-bold text-[#22C55E]" style="font-family: 'Playfair Display', serif;">{{ $summary['current']['products_sold'] }}</p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-[#22C55E] to-[#16A34A] flex items-center justify-center shadow-lg">
                    <i class="fas fa-box text-white text-3xl"></i>
                </div>
            </div>
            @if($summary['evolution']['products_percent'] != 0)
                <div class="flex items-center gap-2 pt-2 border-t border-[#E5DDD3]">
                    @if($summary['evolution']['products_percent'] > 0)
                        <i class="fas fa-arrow-up text-[#22C55E]"></i>
                        <span class="text-sm text-[#22C55E] font-semibold">+{{ abs($summary['evolution']['products_percent']) }}%</span>
                    @else
                        <i class="fas fa-arrow-down text-[#EF4444]"></i>
                        <span class="text-sm text-[#EF4444] font-semibold">{{ $summary['evolution']['products_percent'] }}%</span>
                    @endif
                    <span class="text-xs text-[#8B7355]">vs période précédente</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Graphique : Évolution des ventes --}}
    <div class="premium-card mb-8">
        <h3 class="text-xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-chart-area text-[#ED5F1E] mr-2"></i>
            Évolution des ventes
        </h3>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    {{-- Graphiques côte à côte --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Top produits --}}
        <div class="premium-card">
            <h3 class="text-xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-trophy text-[#ED5F1E] mr-2"></i>
                Top produits (par CA)
            </h3>
            <div class="chart-container" style="height: 250px;">
                <canvas id="topProductsChart"></canvas>
            </div>
            <div class="mt-6 space-y-3">
                @foreach(array_slice($topProducts, 0, 5) as $index => $product)
                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-[#F8F6F3] to-white rounded-xl border border-[#E5DDD3]">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-[#ED5F1E] to-[#FFB800] text-white font-bold text-sm">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-sm font-semibold text-[#2C1810]">{{ $product['name'] }}</span>
                    </div>
                    <span class="text-sm font-bold text-[#ED5F1E]">{{ number_format($product['revenue'], 0, ',', ' ') }} F</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Répartition statuts commandes --}}
        <div class="premium-card">
            <h3 class="text-xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-chart-pie text-[#ED5F1E] mr-2"></i>
                Répartition des statuts
            </h3>
            <div class="chart-container" style="height: 250px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Graphique évolution des ventes
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = @json($salesTimeSeries);
    
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesData.labels,
            datasets: [{
                label: 'Ventes (FCFA)',
                data: salesData.values,
                borderColor: '#ED5F1E',
                backgroundColor: 'rgba(237, 95, 30, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#ED5F1E',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#8B7355',
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR').format(value) + ' F';
                        }
                    },
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#8B7355',
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    }
                }
            }
        }
    });

    // Graphique top produits
    const topProductsData = @json($topProducts);
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    
    new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: topProductsData.map(p => p.name.length > 15 ? p.name.substring(0, 15) + '...' : p.name),
            datasets: [{
                label: 'CA (FCFA)',
                data: topProductsData.map(p => p.revenue),
                backgroundColor: 'rgba(237, 95, 30, 0.8)',
                borderColor: '#ED5F1E',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#8B7355',
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR').format(value) + ' F';
                        }
                    },
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#8B7355',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Graphique répartition statuts
    const statusData = @json($orderStatusDistribution);
    const statusLabels = {
        'pending': 'En attente',
        'paid': 'Payée',
        'in_production': 'En production',
        'ready_to_ship': 'Prêt à expédier',
        'shipped': 'Expédiée',
        'completed': 'Terminée',
        'cancelled': 'Annulée'
    };
    
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusColors = [
        'rgba(234, 179, 8, 0.8)',   // pending - yellow
        'rgba(59, 130, 246, 0.8)',   // paid - blue
        'rgba(147, 51, 234, 0.8)',   // in_production - purple
        'rgba(99, 102, 241, 0.8)',   // ready_to_ship - indigo
        'rgba(168, 85, 247, 0.8)',   // shipped - purple
        'rgba(34, 197, 94, 0.8)',    // completed - green
        'rgba(239, 68, 68, 0.8)'    // cancelled - red
    ];
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData).map(key => statusLabels[key] || key),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: statusColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#8B7355',
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
