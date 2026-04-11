@extends('layouts.admin-master')

@section('title', 'Matière Première : ' . $material->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🧵 Matière Première : {{ $material->name }}</h1>
            <p class="text-muted mb-0">Détails et historique</p>
        </div>
        <div>
            <a href="{{ route('erp.materials.edit', $material) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="{{ route('erp.materials.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Informations</h5>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Nom</label>
                        <p class="mb-0"><strong>{{ $material->name }}</strong></p>
                    </div>

                    @if($material->description)
                    <div class="mb-3">
                        <label class="text-muted small">Description</label>
                        <p class="mb-0">{{ $material->description }}</p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="text-muted small">Unité</label>
                        <p class="mb-0"><strong>{{ $material->unit }}</strong></p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Stock Actuel</label>
                        <p class="mb-0">
                            <span class="h4">{{ $material->stock_quantity ?? 0 }}</span>
                            <small class="text-muted">{{ $material->unit }}</small>
                        </p>
                    </div>

                    @if($material->supplier)
                    <div class="mb-3">
                        <label class="text-muted small">Fournisseur Principal</label>
                        <p class="mb-0">
                            <a href="{{ route('erp.suppliers.show', $material->supplier) }}">
                                {{ $material->supplier->name }}
                            </a>
                        </p>
                    </div>
                    @endif

                    @if($material->min_stock_level)
                    <div class="mb-3">
                        <label class="text-muted small">Stock Minimum</label>
                        <p class="mb-0">{{ $material->min_stock_level }} {{ $material->unit }}</p>
                    </div>
                    @endif

                    @if($material->notes)
                    <div class="mb-3">
                        <label class="text-muted small">Notes</label>
                        <p class="mb-0">{{ $material->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">Statut Stock</h6>
                    @php
                        $stockQty = $material->stock_quantity ?? 0;
                        $minStock = $material->min_stock_level ?? 0;
                    @endphp
                    @if($stockQty <= 0)
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Rupture de stock
                        </div>
                    @elseif($minStock > 0 && $stockQty < $minStock)
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-circle"></i> Stock faible (sous le minimum)
                        </div>
                    @else
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> Stock suffisant
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📊 Historique des Mouvements</h5>
                    <a href="{{ route('erp.stocks.movements', ['material' => $material->id]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-history"></i> Voir tout
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $movements = \Modules\ERP\Models\ErpStockMovement::where('movable_type', \Modules\ERP\Models\ErpRawMaterial::class)
                            ->where('movable_id', $material->id)
                            ->latest()
                            ->take(10)
                            ->get();
                    @endphp

                    @if($movements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantité</th>
                                        <th>Raison</th>
                                        <th>Utilisateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movements as $movement)
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
                                                <strong>{{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}</strong>
                                                {{ $material->unit }}
                                            </td>
                                            <td>{{ $movement->reason ?? '-' }}</td>
                                            <td>{{ $movement->user->name ?? 'Système' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Aucun mouvement enregistré</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

