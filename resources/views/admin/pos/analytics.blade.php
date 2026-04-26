@extends('layouts.admin')

@section('title', 'Analytics POS')
@section('page-title', 'Analytics Point de Vente')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style nonce="{{ csp_nonce() }}">
    .analytics-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .stat-box {
        background: rgba(237, 95, 30, 0.1);
        border: 1px solid rgba(237, 95, 30, 0.3);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #ED5F1E;
    }

    .stat-label {
        color: #94a3b8;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 1rem;
    }

    .date-picker {
        background: rgba(22, 13, 12, 0.8);
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #e2e8f0;
    }

    .date-picker:focus {
        outline: none;
        border-color: #ED5F1E;
    }

    .btn-analytics {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-analytics:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }

    .performance-table {
        width: 100%;
        margin-top: 1rem;
    }

    .performance-table th {
        color: #ED5F1E;
        font-weight: 600;
        padding: 0.75rem;
        border-bottom: 2px solid rgba(212, 165, 116, 0.2);
    }

    .performance-table td {
        color: #e2e8f0;
        padding: 0.75rem;
        border-bottom: 1px solid rgba(212, 165, 116, 0.1);
    }

    .bg-success {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .bg-warning {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .bg-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Filtres --}}
    <div class="analytics-card">
        <h3 class="text-white mb-3">
            <i class="fas fa-filter text-warning me-2"></i>
            Filtres
        </h3>
        
        <div class="row">
            <div class="col-md-3">
                <label class="text-white mb-2">Type de rapport</label>
                <select id="report-type" class="form-control date-picker">
                    <option value="daily">Journalier</option>
                    <option value="period">Période</option>
                    <option value="discrepancy">Écarts de caisse</option>
                </select>
            </div>

            <div class="col-md-3" id="single-date-group">
                <label class="text-white mb-2">Date</label>
                <input type="date" id="single-date" class="form-control date-picker" value="{{ $selectedDate }}">
            </div>

            <div class="col-md-3" id="start-date-group" style="display: none;">
                <label class="text-white mb-2">Date début</label>
                <input type="date" id="start-date" class="form-control date-picker">
            </div>

            <div class="col-md-3" id="end-date-group" style="display: none;">
                <label class="text-white mb-2">Date fin</label>
                <input type="date" id="end-date" class="form-control date-picker">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button id="load-report" class="btn-analytics w-100">
                    <i class="fas fa-chart-line me-2"></i>
                    Charger le rapport
                </button>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button id="export-csv" class="btn btn-secondary w-100" style="border-radius: 12px;">
                    <i class="fas fa-download me-2"></i>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    {{-- Stats globales --}}
    <div class="analytics-card">
        <h3 class="text-white mb-3">
            <i class="fas fa-chart-bar text-warning me-2"></i>
            Statistiques
        </h3>

        <div class="row" id="stats-container">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="stat-sessions">{{ $dailyReport['sessions_count'] ?? 0 }}</div>
                    <div class="stat-label">Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="stat-sales">{{ number_format($dailyReport['sessions_total_sales'] ?? 0, 0, ',', ' ') }} FCFA</div>
                    <div class="stat-label">Ventes totales</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="stat-difference">{{ number_format($dailyReport['total_cash_difference'] ?? 0, 0, ',', ' ') }} FCFA</div>
                    <div class="stat-label">Écart de caisse</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="stat-discrepancies">{{ $dailyReport['discrepancies'] ?? 0 }}</div>
                    <div class="stat-label">Sessions avec écart</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Graphiques --}}
    <div class="row">
        <div class="col-md-6">
            <div class="analytics-card">
                <h4 class="text-white mb-3">Ventes par méthode de paiement</h4>
                <div class="chart-container">
                    <canvas id="payment-methods-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="analytics-card">
                <h4 class="text-white mb-3">Performance opérateurs</h4>
                <div class="chart-container">
                    <canvas id="operators-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau performance --}}
    <div class="analytics-card">
        <h3 class="text-white mb-3">
            <i class="fas fa-users text-warning me-2"></i>
            Détails performance opérateurs
        </h3>

        <table class="performance-table" id="performance-table">
            <thead>
                <tr>
                    <th>Opérateur</th>
                    <th>Sessions</th>
                    <th>Ventes totales</th>
                    <th>Écarts</th>
                    <th>Taux d'écart</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody id="performance-tbody">
                {{-- Rempli via JavaScript --}}
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
let paymentMethodsChart = null;
let operatorsChart = null;

// Gestion affichage filtres
document.getElementById('report-type').addEventListener('change', function() {
    const type = this.value;
    const singleDateGroup = document.getElementById('single-date-group');
    const startDateGroup = document.getElementById('start-date-group');
    const endDateGroup = document.getElementById('end-date-group');

    if (type === 'daily') {
        singleDateGroup.style.display = 'block';
        startDateGroup.style.display = 'none';
        endDateGroup.style.display = 'none';
    } else {
        singleDateGroup.style.display = 'none';
        startDateGroup.style.display = 'block';
        endDateGroup.style.display = 'block';
    }
});

// Charger rapport
document.getElementById('load-report').addEventListener('click', async function() {
    const type = document.getElementById('report-type').value;
    let url, data;

    if (type === 'daily') {
        url = '{{ route("pos.interface.analytics.daily") }}';
        data = { date: document.getElementById('single-date').value };
    } else if (type === 'period') {
        url = '{{ route("pos.interface.analytics.period") }}';
        data = {
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value
        };
    } else {
        url = '{{ route("pos.interface.analytics.discrepancy") }}';
        data = {
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value,
            threshold: 1.00
        };
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            updateDashboard(result.report);
        }
    } catch (error) {
        console.error('Error loading report:', error);
        alert('Erreur lors du chargement du rapport');
    }
});

// Mettre à jour dashboard
function updateDashboard(report) {
    // Stats
    document.getElementById('stat-sessions').textContent = report.sessions_count || 0;
    document.getElementById('stat-sales').textContent = (report.sessions_total_sales || 0).toLocaleString() + ' FCFA';
    document.getElementById('stat-difference').textContent = (report.total_cash_difference || 0).toLocaleString() + ' FCFA';
    document.getElementById('stat-discrepancies').textContent = report.discrepancies || 0;

    // Graphique méthodes paiement
    if (report.payment_methods) {
        updatePaymentMethodsChart(report.payment_methods);
    }

    // Graphique opérateurs
    if (report.performance) {
        updateOperatorsChart(report.performance);
        updatePerformanceTable(report.performance);
    }
}

// Graphique méthodes paiement
function updatePaymentMethodsChart(paymentMethods) {
    const ctx = document.getElementById('payment-methods-chart');
    
    if (paymentMethodsChart) {
        paymentMethodsChart.destroy();
    }

    const labels = Object.keys(paymentMethods);
    const data = labels.map(method => paymentMethods[method].total);

    paymentMethodsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.map(l => l.toUpperCase()),
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(237, 95, 30, 0.8)',
                    'rgba(255, 184, 0, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                ],
                borderColor: 'rgba(22, 13, 12, 0.8)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
}

// Graphique opérateurs
function updateOperatorsChart(performance) {
    const ctx = document.getElementById('operators-chart');
    
    if (operatorsChart) {
        operatorsChart.destroy();
    }

    const operators = Object.keys(performance);
    const sales = operators.map(op => performance[op].total_sales);

    operatorsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: operators,
            datasets: [{
                label: 'Ventes (FCFA)',
                data: sales,
                backgroundColor: 'rgba(237, 95, 30, 0.8)',
                borderColor: 'rgba(237, 95, 30, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#e2e8f0'
                    },
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#e2e8f0'
                    },
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
}

// Tableau performance
function updatePerformanceTable(performance) {
    const tbody = document.getElementById('performance-tbody');
    tbody.innerHTML = '';

    Object.entries(performance).forEach(([operator, data]) => {
        const row = document.createElement('tr');
        
        let statusBadge = '';
        if (data.discrepancy_rate === 0) {
            statusBadge = '<span class="bg-success">Excellent</span>';
        } else if (data.discrepancy_rate < 10) {
            statusBadge = '<span class="bg-warning">Acceptable</span>';
        } else {
            statusBadge = '<span class="bg-danger">À surveiller</span>';
        }

        row.innerHTML = `
            <td>${operator}</td>
            <td>${data.sessions}</td>
            <td>${data.total_sales.toLocaleString()} FCFA</td>
            <td>${data.discrepancies}</td>
            <td>${data.discrepancy_rate.toFixed(1)}%</td>
            <td>${statusBadge}</td>
        `;
        
        tbody.appendChild(row);
    });
}

// Export CSV
document.getElementById('export-csv').addEventListener('click', function() {
    const startDate = document.getElementById('start-date').value || document.getElementById('single-date').value;
    const endDate = document.getElementById('end-date').value || document.getElementById('single-date').value;

    window.location.href = `{{ route('pos.interface.analytics.export') }}?start_date=${startDate}&end_date=${endDate}`;
});

// Initialiser graphiques au chargement
@if(isset($dailyReport))
updateDashboard(@json($dailyReport));
@endif
</script>
@endpush
