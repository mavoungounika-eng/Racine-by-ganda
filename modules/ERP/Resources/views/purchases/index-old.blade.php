@extends('layouts.admin-master')

@section('title', 'Gestion des Achats')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Commandes Fournisseurs</h1>
            <a href="{{ route('erp.purchases.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Commande
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Liste des Achats</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Fournisseur</th>
                                <th>Date</th>
                                <th>Montant Total</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->reference }}</td>
                                    <td>{{ $purchase->supplier->name }}</td>
                                    <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                    <td>{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</td>
                                    <td>
                                        @if($purchase->status === 'received')
                                            <span class="badge bg-success">Reçu</span>
                                        @elseif($purchase->status === 'cancelled')
                                            <span class="badge bg-danger">Annulé</span>
                                        @else
                                            <span class="badge bg-warning">Commandé</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('erp.purchases.show', $purchase) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Aucune commande trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $purchases->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
