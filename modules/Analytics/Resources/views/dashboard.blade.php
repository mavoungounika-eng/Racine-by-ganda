@extends('layouts.internal')

@section('title', 'Analytics - Business Intelligence')
@section('page-title', 'Analytics - Business Intelligence')

@push('styles')
<style>
    /* ===== ANALYTICS PREMIUM DESIGN ===== */
    .analytics-hero {
        background: linear-gradient(135deg, #160D0C 0%, #2A1A18 50%, #ED5F1E 100%);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .analytics-hero::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .kpi-mega {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .kpi-mega:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .kpi-mega .value {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        line-height: 1;
    }
    
    .kpi-mega .label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }
    
    .kpi-mega .growth {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .kpi-mega .growth.positive {
        background: rgba(40, 167, 69, 0.2);
        color: #5CFF7E;
    }
    
    .kpi-mega .growth.negative {
        background: rgba(220, 53, 69, 0.2);
        color: #FF6B6B;
    }
    
    .chart-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: none;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .chart-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .chart-card .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 2px solid #ED5F1E;
        padding: 1rem 1.5rem;
    }
    
    .chart-card .card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #160D0C;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .insight-card {
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .insight-card:hover {
        transform: translateX(5px);
    }
    
    .insight-card.success {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
        border-color: #28A745;
    }
    
    .insight-card.warning {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
        border-color: #FFC107;
    }
    
    .insight-card.danger {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
        border-color: #DC3545;
    }
    
    .insight-card.info {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%);
        border-color: #17A2B8;
    }
    
    .insight-icon {
        font-size: 2rem;
        line-height: 1;
    }
    
    .top-product {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: 12px;
        background: #f8f9fa;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .top-product:hover {
        background: #fff3eb;
        transform: translateX(5px);
    }
    
    .top-product .rank {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        margin-right: 1rem;
    }
    
    .top-product .rank.gold {
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        color: white;
    }
    
    .top-product .rank.silver {
        background: linear-gradient(135deg, #C0C0C0 0%, #A0A0A0 100%);
        color: white;
    }
    
    .top-product .rank.bronze {
        background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
        color: white;
    }
    
    .top-product .rank.normal {
        background: #e9ecef;
        color: #495057;
    }
    
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(40, 167, 69, 0.1);
        color: #28A745;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .live-indicator::before {
        content: '';
        width: 8px;
        height: 8px;
        background: #28A745;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }
    
    .stat-mini {
        text-align: center;
        padding: 1rem;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 1px solid #e9ecef;
    }
    
    .stat-mini .value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #ED5F1E;
    }
    
    .stat-mini .label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    
    {{-- Hero Section avec KPIs principaux --}}
    <div class="analytics-hero">
        <div class="row align-items-center">
            <div class="col-lg-4 mb-3 mb-lg-0">
                <h1 class="text-white mb-2" style="font-size: 2rem; font-weight: 800;">
                    📊 Business Intelligence
                </h1>
                <p class="text-white-50 mb-0">Insights temps réel • Décisions éclairées</p>
                <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                    <span class="live-indicator">
                        Données en direct
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);" data-toggle="dropdown">
                            📥 Exporter <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('analytics.export.report') }}" target="_blank">
                                🖨️ Rapport PDF
                            </a>
                            <a class="dropdown-item" href="{{ route('analytics.export.csv') }}">
                                📊 Export CSV
                            </a>
                            <a class="dropdown-item" href="{{ route('analytics.export.json') }}">
                                💾 Export JSON
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="row">
                    {{-- KPI: Revenus du mois --}}
                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                        <div class="kpi-mega">
                            <div class="value" id="kpi-revenue">{{ $kpis['revenue']['formatted'] }}</div>
                            <div class="label">Revenus ce mois</div>
                            <span class="growth {{ $kpis['revenue']['growth'] >= 0 ? 'positive' : 'negative' }}">
                                {{ $kpis['revenue']['growth'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['revenue']['growth']) }}%
                            </span>
                        </div>
                    </div>
                    
                    {{-- KPI: Commandes --}}
                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                        <div class="kpi-mega">
                            <div class="value" id="kpi-orders">{{ $kpis['orders']['value'] }}</div>
                            <div class="label">Commandes</div>
                            <span class="growth {{ $kpis['orders']['growth'] >= 0 ? 'positive' : 'negative' }}">
                                {{ $kpis['orders']['growth'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['orders']['growth']) }}%
                            </span>
                        </div>
                    </div>
                    
                    {{-- KPI: Panier moyen --}}
                    <div class="col-md-3 col-6">
                        <div class="kpi-mega">
                            <div class="value" id="kpi-cart">{{ $kpis['avg_cart']['formatted'] }}</div>
                            <div class="label">Panier moyen</div>
                        </div>
                    </div>
                    
                    {{-- KPI: Conversion --}}
                    <div class="col-md-3 col-6">
                        <div class="kpi-mega">
                            <div class="value" id="kpi-conversion">{{ $kpis['conversion_rate'] }}%</div>
                            <div class="label">Taux conversion</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats du jour --}}
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-mini">
                <div class="value">{{ number_format($kpis['today_revenue'], 0, ',', ' ') }}</div>
                <div class="label">💰 Revenus aujourd'hui</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-mini">
                <div class="value">{{ $kpis['today_orders'] }}</div>
                <div class="label">📦 Commandes aujourd'hui</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-mini">
                <div class="value">{{ $kpis['new_clients'] }}</div>
                <div class="label">👥 Nouveaux clients (mois)</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-mini">
                <div class="value" id="peak-hour">--:--</div>
                <div class="label">⏰ Heure de pointe</div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Graphique Revenus --}}
        <div class="col-lg-8 mb-4">
            <div class="chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>📈 Évolution des Revenus</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-days="7">7j</button>
                        <button type="button" class="btn btn-outline-secondary" data-days="14">14j</button>
                        <button type="button" class="btn btn-outline-secondary" data-days="30">30j</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Insights Intelligents --}}
        <div class="col-lg-4 mb-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h5>🧠 Insights Intelligents</h5>
                </div>
                <div class="card-body p-3">
                    <div id="insights-container">
                        @forelse($insights as $insight)
                            <div class="insight-card {{ $insight['type'] }} mb-3">
                                <span class="insight-icon">{{ $insight['icon'] }}</span>
                                <div>
                                    <strong>{{ $insight['title'] }}</strong>
                                    <p class="mb-0 small text-muted">{{ $insight['message'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <span class="display-1">✨</span>
                                <p class="text-muted mt-2">Tout fonctionne parfaitement !</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Comparatif Mensuel --}}
        <div class="col-lg-6 mb-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h5>📊 Comparatif Mensuel</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="250"></canvas>
                </div>
            </div>
        </div>

        {{-- Répartition par Catégorie --}}
        <div class="col-lg-3 mb-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h5>🏷️ Par Catégorie</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>

        {{-- Statuts Commandes --}}
        <div class="col-lg-3 mb-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h5>📦 Statuts</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Top Produits --}}
        <div class="col-lg-6 mb-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h5>🏆 Top 5 Produits</h5>
                </div>
                <div class="card-body">
                    @foreach($topProducts as $index => $product)
                        <div class="top-product">
                            <div class="rank {{ $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : 'normal')) }}">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-grow-1">
                                <strong>{{ Str::limit($product['title'], 30) }}</strong>
                                <div class="small text-muted">{{ $product['total_sold'] }} vendus</div>
                            </div>
                            <div class="text-right">
                                <span class="font-weight-bold text-success">{{ $product['revenue_formatted'] }}</span>
                            </div>
                        </div>
                    @endforeach
                    
                    @if(empty($topProducts))
                        <div class="text-center py-4">
                            <span class="display-1">📦</span>
                            <p class="text-muted mt-2">Aucune vente enregistrée</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Heures de pointe --}}
        <div class="col-lg-6 mb-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h5>⏰ Heures de Pointe</h5>
                </div>
                <div class="card-body">
                    <canvas id="peakHoursChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration globale Chart.js
    Chart.defaults.font.family = "'Segoe UI', 'Roboto', sans-serif";
    Chart.defaults.color = '#6c757d';
    
    let revenueChart, monthlyChart, categoryChart, statusChart, peakHoursChart;

    // Charger les graphiques
    loadRevenueChart(30);
    loadMonthlyChart();
    loadCategoryChart();
    loadStatusChart();
    loadPeakHoursChart();

    // Boutons de période
    document.querySelectorAll('[data-days]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-days]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadRevenueChart(parseInt(this.dataset.days));
        });
    });

    // Graphique Revenus
    function loadRevenueChart(days) {
        fetch(`{{ route('analytics.api.revenue-chart') }}?days=${days}`)
            .then(r => r.json())
            .then(data => {
                if (revenueChart) revenueChart.destroy();
                revenueChart = new Chart(document.getElementById('revenueChart'), {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#160D0C',
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: ctx => ctx.parsed.y.toLocaleString('fr-FR') + ' FCFA'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: v => v.toLocaleString('fr-FR')
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            });
    }

    // Graphique Mensuel
    function loadMonthlyChart() {
        fetch('{{ route('analytics.api.monthly-chart') }}')
            .then(r => r.json())
            .then(data => {
                monthlyChart = new Chart(document.getElementById('monthlyChart'), {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                backgroundColor: '#160D0C',
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                ticks: {
                                    callback: v => v.toLocaleString('fr-FR')
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            });
    }

    // Graphique Catégories
    function loadCategoryChart() {
        fetch('{{ route('analytics.api.category-chart') }}')
            .then(r => r.json())
            .then(data => {
                categoryChart = new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, padding: 8 }
                            }
                        },
                        cutout: '60%'
                    }
                });
            });
    }

    // Graphique Statuts
    function loadStatusChart() {
        fetch('{{ route('analytics.api.orders-status') }}')
            .then(r => r.json())
            .then(data => {
                statusChart = new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, padding: 8 }
                            }
                        },
                        cutout: '60%'
                    }
                });
            });
    }

    // Graphique Heures de pointe
    function loadPeakHoursChart() {
        fetch('{{ route('analytics.api.peak-hours') }}')
            .then(r => r.json())
            .then(data => {
                document.getElementById('peak-hour').textContent = data.peak_hour;
                
                peakHoursChart = new Chart(document.getElementById('peakHoursChart'), {
                    type: 'line',
                    data: data.chart,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#160D0C',
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            });
    }

    // Rafraîchissement automatique des KPIs (toutes les 60 secondes)
    setInterval(() => {
        fetch('{{ route('analytics.api.kpis') }}')
            .then(r => r.json())
            .then(data => {
                document.getElementById('kpi-revenue').textContent = data.revenue.formatted;
                document.getElementById('kpi-orders').textContent = data.orders.value;
                document.getElementById('kpi-cart').textContent = data.avg_cart.formatted;
                document.getElementById('kpi-conversion').textContent = data.conversion_rate + '%';
            });
    }, 60000);
});
</script>
@endpush

