@extends('layouts.admin-master')

@section('title', 'ERP - Mouvements de Stock')
@section('page-title', 'Mouvements de Stock')

@section('content')
<div class="mb-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('erp.stocks.index') }}">Stocks</a></li>
                    <li class="breadcrumb-item active">Mouvements</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h1 class="h2 mb-0">📋 Historique des Mouvements</h1>
                <a href="{{ route('erp.stocks.movements.export', request()->query()) }}" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exporter Excel
                </a>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('erp.stocks.movements') }}" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted">Date début</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted">Date fin</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted">Type</label>
                    <select name="type" class="form-control">
                        <option value="">Tous</option>
                        <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Entrées</option>
                        <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Sorties</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des mouvements --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($movements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Date</th>
                                <th class="border-0">Type</th>
                                <th class="border-0">Produit/Matière</th>
                                <th class="border-0">Quantité</th>
                                <th class="border-0">Raison</th>
                                <th class="border-0">De → Vers</th>
                                <th class="border-0">Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                            <tr>
                                <td class="align-middle">
                                    <small>{{ $movement->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="align-middle">
                                    @if($movement->type === 'in')
                                        <span class="badge bg-success">⬇️ Entrée</span>
                                    @else
                                        <span class="badge bg-danger">⬆️ Sortie</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <strong>
                                        {{ $movement->stockable ? ($movement->stockable->title ?? $movement->stockable->name) : 'N/A' }}
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    <span class="font-weight-bold">{{ $movement->quantity }}</span>
                                </td>
                                <td class="align-middle">
                                    <small class="text-muted">{{ $movement->reason ?? '-' }}</small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $movement->from_location ?? '-' }} → {{ $movement->to_location ?? '-' }}</small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $movement->user ? $movement->user->name : 'Système' }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="p-3">
                    {{ $movements->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <span class="display-1">📋</span>
                    <h5 class="text-muted mt-3">Aucun mouvement de stock</h5>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
