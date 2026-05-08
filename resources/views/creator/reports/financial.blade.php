@extends('layouts.creator')

@section('title', 'Rapport Financier - RACINE BY GANDA')
@section('page-title', 'Rapport Financier')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .report-stat {
        background: white;
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid #F0EBE5;
        text-align: center;
    }
    .report-stat .value {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--racine-orange);
    }
    .report-stat .label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8B7355;
        margin-top: 0.3rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Filtres période --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('creator.export.finances') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-bold">Période</label>
                    <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>Tout</option>
                        <option value="month" {{ ($period ?? '') === 'month' ? 'selected' : '' }}>Ce mois</option>
                        <option value="year" {{ ($period ?? '') === 'year' ? 'selected' : '' }}>Cette année</option>
                    </select>
                </div>
                <div class="col-auto">
                    <a href="{{ route('creator.export.finances') }}?period={{ $period ?? 'all' }}&format=json"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i>JSON
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($dateFrom || $dateTo)
    <p class="text-muted small mb-4">
        Période :
        {{ $dateFrom ? $dateFrom->format('d/m/Y') : '—' }}
        →
        {{ $dateTo ? $dateTo->format('d/m/Y') : 'aujourd\'hui' }}
    </p>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="report-stat">
                <div class="value">{{ number_format($grossRevenue, 0, ',', ' ') }} XAF</div>
                <div class="label">Chiffre d'affaires brut</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-stat">
                <div class="value" style="color: #dc2626;">{{ number_format($commission, 0, ',', ' ') }} XAF</div>
                <div class="label">Commission plateforme (20%)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-stat">
                <div class="value" style="color: #16a34a;">{{ number_format($netRevenue, 0, ',', ' ') }} XAF</div>
                <div class="label">Revenu net</div>
            </div>
        </div>
    </div>

    {{-- Tableau commandes --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <h6 class="fw-bold mb-0">Commandes récentes ({{ $recentPaidOrders->count() }})</h6>
        </div>
        <div class="card-body p-0">
            @if($recentPaidOrders->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    Aucune commande sur cette période
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">#Commande</th>
                                <th>Date</th>
                                <th class="text-end">Brut</th>
                                <th class="text-end">Commission</th>
                                <th class="text-end pe-4">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPaidOrders as $order)
                            <tr>
                                <td class="px-4">
                                    <a href="{{ route('creator.orders.show', $order) }}" class="fw-semibold text-decoration-none">
                                        #{{ $order->id }}
                                    </a>
                                </td>
                                <td class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">{{ number_format($order->creator_gross, 0, ',', ' ') }} XAF</td>
                                <td class="text-end text-danger">-{{ number_format($order->creator_commission, 0, ',', ' ') }} XAF</td>
                                <td class="text-end pe-4 fw-semibold text-success">{{ number_format($order->creator_net, 0, ',', ' ') }} XAF</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
