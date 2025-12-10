@extends('layouts.admin-master')

@section('title', 'Rapport d\'Achats - ERP')
@section('page-title', 'Rapport d\'Achats')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="h3 mb-1">🛒 Rapport d'Achats</h2>
                    <p class="text-muted mb-0">
                        Période : 
                        @if($period === 'month')
                            {{ $dateFrom->format('F Y') }}
                        @elseif($period === 'year')
                            Année {{ $dateFrom->format('Y') }}
                        @else
                            {{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
                <div>
                    <a href="{{ route('erp.reports.purchases', ['period' => $period, 'format' => 'json']) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Export JSON
                    </a>
                    <button onclick="window.print()" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Commandes</h6>
                    <h3 class="mb-0">{{ $stats['total_purchases'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Montant Total</h6>
                    <h3 class="mb-0">{{ number_format($stats['total_amount'], 0, ',', ' ') }} XAF</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Par Statut</h6>
                    <div class="small">
                        @foreach($stats['by_status'] as $status => $data)
                        <div class="d-flex justify-content-between">
                            <span>{{ ucfirst($status) }}:</span>
                            <strong>{{ $data['count'] }} ({{ number_format($data['total'], 0, ',', ' ') }} XAF)</strong>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Top Fournisseurs</h6>
                    <div class="small">
                        @foreach($stats['by_supplier']->take(3) as $supplier)
                        <div class="d-flex justify-content-between">
                            <span>{{ \Illuminate\Support\Str::limit($supplier['supplier'], 15) }}:</span>
                            <strong>{{ number_format($supplier['total'], 0, ',', ' ') }} XAF</strong>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Liste des Achats --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Détail des Achats</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Référence</th>
                                    <th class="border-0">Fournisseur</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Statut</th>
                                    <th class="border-0 text-end">Montant</th>
                                    <th class="border-0 text-end">Nb Articles</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('erp.purchases.show', $purchase) }}" class="text-decoration-none">
                                            <strong>{{ $purchase->reference }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $purchase->supplier->name ?? '-' }}</td>
                                    <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($purchase->status === 'received')
                                            <span class="badge bg-success">Réceptionné</span>
                                        @elseif($purchase->status === 'ordered')
                                            <span class="badge bg-warning">Commandé</span>
                                        @else
                                            <span class="badge bg-danger">Annulé</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><strong>{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</strong></td>
                                    <td class="text-end">{{ $purchase->items->count() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucun achat pour cette période</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <a href="{{ route('erp.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour au Dashboard
                </a>
                <div>
                    <a href="{{ route('erp.reports.purchases', ['period' => $period, 'format' => 'json']) }}" class="btn btn-outline-primary">
                        <i class="fas fa-download me-1"></i> Export JSON
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i> Imprimer / PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        .btn, .card-header .btn {
            display: none !important;
        }
    }
</style>
@endpush
@endsection

