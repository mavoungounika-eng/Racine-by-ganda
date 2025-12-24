@extends('layouts.creator')

@section('title', 'Dashboard Financier - RACINE BY GANDA')
@section('page-title', 'Dashboard Financier')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- HEADER --}}
        <div class="creator-card mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="mb-2" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810;">
                        <i class="fas fa-chart-line me-3 text-orange-500"></i>
                        Dashboard Financier
                    </h3>
                    <p class="mb-0 text-muted">
                        Suivez vos revenus, analysez vos performances et gérez vos paiements
                    </p>
                </div>
                <div class="creator-avatar creator-avatar-lg">
                    <i class="fas fa-coins" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>

        {{-- PERIOD SELECTOR TABS --}}
        <div class="creator-card mb-4 p-0">
            <ul class="nav nav-tabs border-0 bg-light rounded-top px-4 pt-2">
                <li class="nav-item">
                    <a href="{{ route('creator.finances.index', ['period' => 'week']) }}" 
                       class="nav-link border-0 py-3 px-4 {{ $period === 'week' ? 'active fw-bold text-orange-500 bg-white' : 'text-muted' }}"
                       style="{{ $period === 'week' ? 'border-bottom: 3px solid #ED5F1E !important;' : '' }}">
                        <i class="fas fa-calendar-week me-2"></i>Cette Semaine
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('creator.finances.index', ['period' => 'month']) }}" 
                       class="nav-link border-0 py-3 px-4 {{ $period === 'month' ? 'active fw-bold text-orange-500 bg-white' : 'text-muted' }}"
                       style="{{ $period === 'month' ? 'border-bottom: 3px solid #ED5F1E !important;' : '' }}">
                        <i class="fas fa-calendar-alt me-2"></i>Ce Mois
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('creator.finances.index', ['period' => 'year']) }}" 
                       class="nav-link border-0 py-3 px-4 {{ $period === 'year' ? 'active fw-bold text-orange-500 bg-white' : 'text-muted' }}"
                       style="{{ $period === 'year' ? 'border-bottom: 3px solid #ED5F1E !important;' : '' }}">
                        <i class="fas fa-calendar me-2"></i>Cette Année
                    </a>
                </li>
            </ul>
        </div>

        {{-- STATS GRID --}}
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="creator-stat-card h-100">
                    <div class="creator-stat-label mb-2">Prix Produits HT</div>
                    <div class="creator-stat-value">{{ number_format($stats['product_price_ht'], 0, ',', ' ') }} <span class="fs-6 text-muted fw-normal">FCFA</span></div>
                    <i class="fas fa-tag position-absolute end-0 bottom-0 m-3 text-muted opacity-25 fa-2x"></i>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="creator-stat-card h-100">
                    <div class="creator-stat-label mb-2">Frais de Service (5%)</div>
                    <div class="creator-stat-value text-orange-500">{{ number_format($stats['service_fee'], 0, ',', ' ') }} <span class="fs-6 text-muted fw-normal">FCFA</span></div>
                    <i class="fas fa-hand-holding-usd position-absolute end-0 bottom-0 m-3 text-muted opacity-25 fa-2x"></i>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="creator-stat-card h-100">
                    <div class="creator-stat-label mb-2">TVA (18%)</div>
                    <div class="creator-stat-value text-muted">{{ number_format($stats['vat'], 0, ',', ' ') }} <span class="fs-6 text-muted fw-normal">FCFA</span></div>
                    <i class="fas fa-percentage position-absolute end-0 bottom-0 m-3 text-muted opacity-25 fa-2x"></i>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="creator-stat-card-premium h-100">
                    <div class="creator-stat-label mb-2 text-dark">Total TTC</div>
                    <div class="creator-stat-value text-success">{{ number_format($stats['total_ttc'], 0, ',', ' ') }} <span class="fs-6 text-muted fw-normal">FCFA</span></div>
                    <i class="fas fa-wallet position-absolute end-0 bottom-0 m-3 text-success opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- REVENUE BREAKDOWN --}}
            <div class="col-12 col-lg-8">
                <div class="creator-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="mb-0 fw-bold text-dark">Détail de la Facturation</h4>
                        <span class="creator-badge creator-badge-info">
                            <i class="fas fa-info-circle me-1"></i> Info
                        </span>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 border bg-light text-center h-100 d-flex flex-column justify-content-center">
                                <div class="text-uppercase text-muted fw-bold small mb-2">Votre Revenu Net (HT)</div>
                                <div class="display-6 fw-bold text-success mb-2">{{ number_format($stats['creator_revenue'], 0, ',', ' ') }} <small class="fs-6 text-muted">FCFA</small></div>
                                <div class="small text-success bg-success bg-opacity-10 py-1 px-2 rounded-pill d-inline-block mx-auto">
                                    <i class="fas fa-check me-1"></i> Montant perçu
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 border bg-light text-center h-100 d-flex flex-column justify-content-center">
                                <div class="text-uppercase text-muted fw-bold small mb-2">Frais de Plateforme</div>
                                <div class="display-6 fw-bold text-orange-500 mb-2">{{ number_format($stats['service_fee'], 0, ',', ' ') }} <small class="fs-6 text-muted">FCFA</small></div>
                                <div class="small text-orange-500 bg-orange-100 py-1 px-2 rounded-pill d-inline-block mx-auto">
                                    5% commission
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border-0 d-flex align-items-start gap-3 mb-0">
                        <i class="fas fa-lightbulb text-warning mt-1 fa-lg"></i>
                        <p class="mb-0 small text-muted">
                            <strong>Le saviez-vous ?</strong> Le client paie le montant TTC. RACINE collecte ce montant, reverse la TVA à l'État, prélève sa commission de 5%, et vous reverse le montant HT restant directement sur votre compte Stripe.
                        </p>
                    </div>
                </div>
            </div>

            {{-- STRIPE STATUS --}}
            <div class="col-12 col-lg-4">
                @if($stripeAccount)
                <div class="creator-card h-100 {{ $stripeAccount->payouts_enabled ? 'border-success' : 'border-warning' }}" style="border-width: 1px;">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3 {{ $stripeAccount->payouts_enabled ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }}" style="width: 80px; height: 80px;">
                            <i class="fab fa-stripe fa-3x"></i>
                        </div>
                        <h4 class="fw-bold mb-2">{{ $stripeAccount->payouts_enabled ? 'Paiements Activés' : 'Configuration Requise' }}</h4>
                        <p class="text-muted small mb-0">
                            {{ $stripeAccount->payouts_enabled ? 'Votre compte est prêt à recevoir des versements automatiques.' : 'Veuillez finaliser la configuration de votre compte pour recevoir vos revenus.' }}
                        </p>
                    </div>

                    @if(!$stripeAccount->payouts_enabled)
                    <div class="d-grid">
                        <a href="{{ route('creator.settings.stripe.connect') }}" class="creator-btn justify-content-center">
                            <i class="fas fa-cog me-2"></i> Configurer Stripe
                        </a>
                    </div>
                    @else
                    <div class="d-grid">
                        <button class="btn btn-outline-success disabled border-0 bg-success bg-opacity-10">
                            <i class="fas fa-check-circle me-2"></i> Compte Vérifié
                        </button>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- GRAPH --}}
        <div class="creator-card mb-4">
            <h4 class="mb-4 fw-bold text-dark">Évolution des Ventes</h4>
            <div style="height: 300px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        {{-- RECENT ORDERS TABLE --}}
        <div class="creator-card p-0 overflow-hidden">
            <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold text-dark">Dernières Commandes Payées</h4>
                <a href="{{ route('creator.orders.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted">
                    Tout voir <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            
            <div class="table-responsive">
                @if($recentOrders->count() > 0)
                <table class="creator-table mb-0">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Date</th>
                            <th class="text-end">Prix HT</th>
                            <th class="text-end">Frais (5%)</th>
                            <th class="text-end">TVA (18%)</th>
                            <th class="text-end">Total TTC</th>
                            <th class="text-end">Votre Revenu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td class="fw-bold text-dark">#{{ $order->order_number }}</td>
                            <td class="text-muted">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">{{ number_format($order->creator_product_ht, 0, ',', ' ') }}</td>
                            <td class="text-end text-muted">{{ number_format($order->creator_service_fee, 0, ',', ' ') }}</td>
                            <td class="text-end text-muted">{{ number_format($order->creator_vat, 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($order->creator_total_ttc, 0, ',', ' ') }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($order->creator_revenue, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-25">
                        <i class="fas fa-receipt fa-4x"></i>
                    </div>
                    <h5 class="text-muted">Aucune commande récente</h5>
                </div>
                @endif
            </div>
        </div>
        
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = @json($salesChartData);
    
    // Gradient pour le graph
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(237, 95, 30, 0.2)');
    gradient.addColorStop(1, 'rgba(237, 95, 30, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.label),
            datasets: [{
                label: 'Chiffre d\'Affaires (FCFA)',
                data: salesData.map(d => d.value),
                borderColor: '#ED5F1E',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ED5F1E',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#2C1810',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString('fr-FR') + ' FCFA';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fr-FR');
                        },
                        font: {
                            family: "'Inter', sans-serif"
                        },
                        color: '#6c757d'
                    },
                    grid: {
                        color: '#f0f0f0',
                        borderDash: [5, 5]
                    },
                    border: {
                        display: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif"
                        },
                        color: '#6c757d'
                    },
                    border: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });
</script>
@endpush
@endsection
