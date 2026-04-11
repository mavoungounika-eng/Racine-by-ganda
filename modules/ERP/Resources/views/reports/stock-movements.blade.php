@extends('layouts.admin-master')

@section('title', 'Rapport des Mouvements de Stock - ERP')
@section('page-title', 'Rapport des Mouvements de Stock')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="h3 mb-1">📊 Mouvements de Stock</h2>
                    <p class="text-muted mb-0">
                        Période : 
                        @if($period === '7d')
                            7 derniers jours
                        @elseif($period === '30d')
                            30 derniers jours
                        @elseif($period === 'month')
                            Mois en cours
                        @else
                            Année en cours
                        @endif
                    </p>
                </div>
                <div>
                    <a href="{{ route('erp.reports.stock-movements', ['period' => $period, 'format' => 'json']) }}" class="btn btn-outline-primary btn-sm">
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
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Entrées</h6>
                    <h3 class="mb-0">+{{ number_format($stats['total_in'], 0, ',', ' ') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Sorties</h6>
                    <h3 class="mb-0">-{{ number_format($stats['total_out'], 0, ',', ' ') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Solde Net</h6>
                    <h3 class="mb-0 {{ ($stats['total_in'] - $stats['total_out']) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ ($stats['total_in'] - $stats['total_out']) >= 0 ? '+' : '' }}{{ number_format($stats['total_in'] - $stats['total_out'], 0, ',', ' ') }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Répartition par Raison --}}
    @if($stats['by_reason']->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Répartition par Raison</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Raison</th>
                                    <th class="text-end">Nombre</th>
                                    <th class="text-end">Quantité Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['by_reason'] as $reason => $data)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $reason)) }}</td>
                                    <td class="text-end">{{ $data->count }}</td>
                                    <td class="text-end">{{ number_format($data->total_qty, 0, ',', ' ') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Liste des Mouvements --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Historique des Mouvements</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">Produit/Matière</th>
                                    <th class="border-0 text-end">Quantité</th>
                                    <th class="border-0">Raison</th>
                                    <th class="border-0">Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($movement->type === 'in')
                                            <span class="badge bg-success">Entrée</span>
                                        @else
                                            <span class="badge bg-danger">Sortie</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($movement->stockable)
                                            {{ $movement->stockable->title ?? $movement->stockable->name ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">Produit supprimé</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $movement->type === 'in' ? 'text-success' : 'text-danger' }}">
                                            {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                        </strong>
                                    </td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $movement->reason ?? '-')) }}</td>
                                    <td>{{ $movement->user->name ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucun mouvement pour cette période</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($movements->hasPages())
                    <div class="card-footer bg-white border-0">
                        {{ $movements->links() }}
                    </div>
                    @endif
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
                    <a href="{{ route('erp.reports.stock-movements', ['period' => $period, 'format' => 'json']) }}" class="btn btn-outline-primary">
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

