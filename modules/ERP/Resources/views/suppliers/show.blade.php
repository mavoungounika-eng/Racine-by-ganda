@extends('layouts.admin-master')

@section('title', 'Fournisseur : ' . $fournisseur->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🏢 Fournisseur : {{ $fournisseur->name }}</h1>
            <p class="text-muted mb-0">Détails et historique</p>
        </div>
        <div>
            <a href="{{ route('erp.suppliers.edit', $fournisseur) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="{{ route('erp.suppliers.index') }}" class="btn btn-secondary">
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
                        <p class="mb-0"><strong>{{ $fournisseur->name }}</strong></p>
                    </div>

                    @if($fournisseur->email)
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <p class="mb-0">
                            <a href="mailto:{{ $fournisseur->email }}">{{ $fournisseur->email }}</a>
                        </p>
                    </div>
                    @endif

                    @if($fournisseur->phone)
                    <div class="mb-3">
                        <label class="text-muted small">Téléphone</label>
                        <p class="mb-0">
                            <a href="tel:{{ $fournisseur->phone }}">{{ $fournisseur->phone }}</a>
                        </p>
                    </div>
                    @endif

                    @if($fournisseur->address)
                    <div class="mb-3">
                        <label class="text-muted small">Adresse</label>
                        <p class="mb-0">{{ $fournisseur->address }}</p>
                    </div>
                    @endif

                    @if($fournisseur->tax_id)
                    <div class="mb-3">
                        <label class="text-muted small">N° Fiscal</label>
                        <p class="mb-0">{{ $fournisseur->tax_id }}</p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="text-muted small">Statut</label>
                        <p class="mb-0">
                            @if($fournisseur->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </p>
                    </div>

                    @if($fournisseur->notes)
                    <div class="mb-3">
                        <label class="text-muted small">Notes</label>
                        <p class="mb-0">{{ $fournisseur->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">Statistiques</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Matières premières</span>
                        <strong>{{ $fournisseur->rawMaterials->count() ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Achats</span>
                        <strong>{{ $fournisseur->purchases->count() ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">📦 Matières Premières</h5>
                </div>
                <div class="card-body">
                    @if($fournisseur->rawMaterials && $fournisseur->rawMaterials->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Unité</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fournisseur->rawMaterials as $material)
                                        <tr>
                                            <td>{{ $material->name }}</td>
                                            <td>{{ $material->unit }}</td>
                                            <td>{{ $material->stock_quantity ?? 0 }}</td>
                                            <td>
                                                <a href="{{ route('erp.materials.edit', $material) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Aucune matière première associée</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🛒 Historique des Achats</h5>
                    <a href="{{ route('erp.purchases.create', ['supplier_id' => $fournisseur->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nouvel achat
                    </a>
                </div>
                <div class="card-body">
                    @if($fournisseur->purchases && $fournisseur->purchases->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fournisseur->purchases->take(10) as $purchase)
                                        <tr>
                                            <td>{{ $purchase->reference }}</td>
                                            <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                            <td>{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</td>
                                            <td>
                                                <span class="badge bg-info">{{ $purchase->status ?? 'pending' }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('erp.purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($fournisseur->purchases->count() > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('erp.purchases.index', ['supplier' => $fournisseur->id]) }}" class="btn btn-sm btn-outline-primary">
                                    Voir tous les achats ({{ $fournisseur->purchases->count() }})
                                </a>
                            </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">Aucun achat enregistré</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

