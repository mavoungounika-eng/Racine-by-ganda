@extends('layouts.admin-master')

@section('title', 'Détail Commande ' . $purchase->reference)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Commande {{ $purchase->reference }}</h1>
        <div>
            <a href="{{ route('erp.purchases.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            @if($purchase->status === 'ordered')
                <form action="{{ route('erp.purchases.update-status', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmer la réception de la marchandise ? Cela mettra à jour les stocks.')">
                    @csrf
                    <input type="hidden" name="status" value="received">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Marquer comme Reçu
                    </button>
                </form>
                <form action="{{ route('erp.purchases.update-status', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Annuler cette commande ?')">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle"></i> Annuler
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Statut :</th>
                            <td>
                                @if($purchase->status === 'received')
                                    <span class="badge bg-success">Reçu</span>
                                @elseif($purchase->status === 'cancelled')
                                    <span class="badge bg-danger">Annulé</span>
                                @else
                                    <span class="badge bg-warning">Commandé</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Fournisseur :</th>
                            <td>
                                <a href="{{ route('erp.suppliers.show', $purchase->supplier) }}">
                                    {{ $purchase->supplier->name }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Date :</th>
                            <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Livraison prévue :</th>
                            <td>{{ $purchase->expected_delivery_date ? $purchase->expected_delivery_date->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Créé par :</th>
                            <td>{{ $purchase->user->name }}</td>
                        </tr>
                    </table>
                    
                    @if($purchase->notes)
                        <div class="mt-3">
                            <strong>Notes :</strong>
                            <p class="text-muted">{{ $purchase->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Articles</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Quantité</th>
                                    <th>Prix Unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                    <tr>
                                        <td>
                                            @if($item->purchasable)
                                                {{ $item->purchasable->name }}
                                            @else
                                                <span class="text-danger">Article supprimé</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price, 0, ',', ' ') }} XAF</td>
                                        <td>{{ number_format($item->total_price, 0, ',', ' ') }} XAF</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right font-weight-bold">Total Général :</td>
                                    <td class="font-weight-bold">{{ number_format($purchase->total_amount, 0, ',', ' ') }} XAF</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
