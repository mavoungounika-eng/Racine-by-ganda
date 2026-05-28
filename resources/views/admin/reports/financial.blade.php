@extends('layouts.admin-master')

@section('title', 'Rapport Financier - Admin')
@section('page-title', 'Rapport Financier')
@section('page-subtitle', 'Synthèse financière par période')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <span class="badge bg-secondary">{{ ucfirst($period) }}</span>
        <span class="text-muted ms-2 small">
            Du {{ $dateFrom->format('d/m/Y') }} au {{ $dateTo->format('d/m/Y') }}
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'json']) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-code me-1"></i>JSON
        </a>
        <a href="{{ route('admin.export.orders') }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Retour exports
        </a>
    </div>
</div>

{{-- KPIs --}}
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Revenu Total</h6>
                <h3 class="mb-0 text-success">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Commandes Totales</h6>
                <h3 class="mb-0">{{ number_format($stats['total_orders'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Commandes Payées</h6>
                <h3 class="mb-0 text-primary">{{ number_format($stats['paid_orders'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Panier Moyen</h6>
                <h3 class="mb-0">{{ number_format($stats['average_order_value'] ?? 0, 0, ',', ' ') }} FCFA</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Par statut --}}
    <div class="col-md-6">
        <div class="card card-racine h-100">
            <div class="card-header">
                <h6 class="mb-0">Commandes par statut</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th class="text-end">Nb</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['by_status'] as $status => $row)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $status }}</span></td>
                                <td class="text-end">{{ $row->count }}</td>
                                <td class="text-end">{{ number_format($row->total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Par moyen de paiement --}}
    <div class="col-md-6">
        <div class="card card-racine h-100">
            <div class="card-header">
                <h6 class="mb-0">Commandes par moyen de paiement</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Méthode</th>
                            <th class="text-end">Nb</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['by_payment_method'] as $method => $row)
                            <tr>
                                <td>{{ $method ?: 'N/A' }}</td>
                                <td class="text-end">{{ $row->count }}</td>
                                <td class="text-end">{{ number_format($row->total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
