@extends('layouts.admin-master')

@section('title', 'Réception — ' . $purchase->reference)
@section('page-title', 'Détail Réception')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📦 Réception — {{ $purchase->reference }}</h1>
            <p class="text-muted mb-0">
                BL : <strong>{{ $reception->bl_number ?? '—' }}</strong>
                &nbsp;·&nbsp; Date : {{ $reception->reception_date->format('d/m/Y') }}
                &nbsp;·&nbsp; Par : {{ $reception->user->name }}
            </p>
        </div>
        <div>
            <a href="{{ route('erp.purchases.show', $purchase) }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Retour commande
            </a>
            <button type="button" class="btn btn-outline-primary btn-print-trigger">
                <i class="fas fa-print me-1"></i> Imprimer
            </button>
        </div>
    </div>

    <div class="row">

        {{-- Statut + infos --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Informations</h6>

                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Statut</span>
                        @if($reception->status === 'complete')
                            <span class="badge bg-success">✅ Complète</span>
                        @elseif($reception->status === 'partial')
                            <span class="badge bg-warning text-dark">⚠️ Partielle</span>
                        @else
                            <span class="badge bg-danger">❌ Refusée</span>
                        @endif
                    </div>

                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Commande</span>
                        <a href="{{ route('erp.purchases.show', $purchase) }}">{{ $purchase->reference }}</a>
                    </div>

                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Fournisseur</span>
                        <strong>{{ $purchase->supplier->name }}</strong>
                    </div>

                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Date réception</span>
                        <span>{{ $reception->reception_date->format('d/m/Y') }}</span>
                    </div>

                    @if($reception->bl_number)
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">N° BL</span>
                        <code>{{ $reception->bl_number }}</code>
                    </div>
                    @endif

                    @if($reception->notes)
                    <div class="mt-3">
                        <span class="text-muted small">Notes :</span>
                        <p class="mb-0 small">{{ $reception->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Résumé quantités --}}
            @php
                $totalOrdered  = $reception->items->sum('quantity_ordered');
                $totalReceived = $reception->items->sum('quantity_received');
                $totalRefused  = $reception->items->sum('quantity_refused');
                $totalEcart    = $totalOrdered - $totalReceived - $totalRefused;
            @endphp
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Résumé Quantités</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Commandé</span>
                        <strong>{{ number_format($totalOrdered, 2, ',', ' ') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-success">Reçu</span>
                        <strong class="text-success">{{ number_format($totalReceived, 2, ',', ' ') }}</strong>
                    </div>
                    @if($totalRefused > 0)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-danger">Refusé</span>
                        <strong class="text-danger">{{ number_format($totalRefused, 2, ',', ' ') }}</strong>
                    </div>
                    @endif
                    @if($totalEcart > 0)
                    <div class="d-flex justify-content-between">
                        <span class="text-warning">Écart manquant</span>
                        <strong class="text-warning">{{ number_format($totalEcart, 2, ',', ' ') }}</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tableau articles --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Détail Articles</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Matière Première</th>
                                    <th class="text-end">Commandé</th>
                                    <th class="text-end">Reçu</th>
                                    <th class="text-end">Refusé</th>
                                    <th class="text-end">Écart</th>
                                    <th class="text-end">Prix Réel</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reception->items as $receptionItem)
                                @php
                                    $purchaseItem = $receptionItem->purchaseItem;
                                    $name = $purchaseItem && $purchaseItem->purchasable
                                        ? $purchaseItem->purchasable->name
                                        : 'Article supprimé';
                                    $ecart = $receptionItem->quantity_ordered
                                           - $receptionItem->quantity_received
                                           - $receptionItem->quantity_refused;
                                    $lineStatus = $receptionItem->status;
                                @endphp
                                <tr>
                                    <td><strong>{{ $name }}</strong></td>
                                    <td class="text-end">{{ number_format($receptionItem->quantity_ordered, 2, ',', ' ') }}</td>
                                    <td class="text-end text-success fw-bold">{{ number_format($receptionItem->quantity_received, 2, ',', ' ') }}</td>
                                    <td class="text-end {{ $receptionItem->quantity_refused > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ number_format($receptionItem->quantity_refused, 2, ',', ' ') }}
                                        @if($receptionItem->refuse_reason)
                                            <br><small class="text-muted">{{ $receptionItem->refuse_reason }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $ecart > 0 ? 'text-warning fw-bold' : 'text-muted' }}">
                                        {{ $ecart > 0 ? number_format($ecart, 2, ',', ' ') : '—' }}
                                    </td>
                                    <td class="text-end">{{ number_format($receptionItem->unit_price_received, 0, ',', ' ') }} XAF</td>
                                    <td>
                                        @if($lineStatus === 'complete')
                                            <span class="badge bg-success">✅ Complet</span>
                                        @elseif($lineStatus === 'partial')
                                            <span class="badge bg-warning text-dark">⚠️ Partiel</span>
                                        @else
                                            <span class="badge bg-danger">❌ Refusé</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
