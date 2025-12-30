@extends('layouts.admin-master')

@section('title', 'Suggestions de Réapprovisionnement - ERP')
@section('page-title', 'Suggestions de Réapprovisionnement')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="h3 mb-1">💡 Suggestions de Réapprovisionnement</h2>
                    <p class="text-muted mb-0">Produits nécessitant un réapprovisionnement</p>
                </div>
                <div>
                    <a href="{{ route('erp.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(empty($suggestions))
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <span class="display-1">✅</span>
                        <h4 class="mt-3">Tous les stocks sont suffisants !</h4>
                        <p class="text-muted">Aucun réapprovisionnement nécessaire pour le moment.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Urgence Critique --}}
        @if(isset($grouped['critical']) && $grouped['critical']->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">🚨 Urgence Critique (Rupture de Stock)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0">Produit</th>
                                        <th class="border-0 text-end">Stock Actuel</th>
                                        <th class="border-0 text-end">Seuil</th>
                                        <th class="border-0 text-end">Quantité Suggérée</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grouped['critical'] as $suggestion)
                                    <tr>
                                        <td><strong>{{ $suggestion['product']->title }}</strong></td>
                                        <td class="text-end text-danger"><strong>{{ $suggestion['current_stock'] }}</strong></td>
                                        <td class="text-end">{{ $suggestion['threshold'] }}</td>
                                        <td class="text-end"><strong>{{ $suggestion['suggested_quantity'] }}</strong></td>
                                        <td>
                                            <a href="{{ route('erp.stocks.adjust', $suggestion['product']) }}" class="btn btn-sm btn-danger">
                                                <i class="fas fa-plus me-1"></i> Réapprovisionner
                                            </a>
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
        @endif

        {{-- Urgence Élevée --}}
        @if(isset($grouped['high']) && $grouped['high']->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-warning">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">⚠️ Urgence Élevée (Stock < 5)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0">Produit</th>
                                        <th class="border-0 text-end">Stock Actuel</th>
                                        <th class="border-0 text-end">Seuil</th>
                                        <th class="border-0 text-end">Quantité Suggérée</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grouped['high'] as $suggestion)
                                    <tr>
                                        <td><strong>{{ $suggestion['product']->title }}</strong></td>
                                        <td class="text-end text-warning"><strong>{{ $suggestion['current_stock'] }}</strong></td>
                                        <td class="text-end">{{ $suggestion['threshold'] }}</td>
                                        <td class="text-end"><strong>{{ $suggestion['suggested_quantity'] }}</strong></td>
                                        <td>
                                            <a href="{{ route('erp.stocks.adjust', $suggestion['product']) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-plus me-1"></i> Réapprovisionner
                                            </a>
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
        @endif

        {{-- Urgence Moyenne --}}
        @if(isset($grouped['medium']) && $grouped['medium']->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">ℹ️ Urgence Moyenne (Stock < 10)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0">Produit</th>
                                        <th class="border-0 text-end">Stock Actuel</th>
                                        <th class="border-0 text-end">Seuil</th>
                                        <th class="border-0 text-end">Quantité Suggérée</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grouped['medium'] as $suggestion)
                                    <tr>
                                        <td><strong>{{ $suggestion['product']->title }}</strong></td>
                                        <td class="text-end text-info"><strong>{{ $suggestion['current_stock'] }}</strong></td>
                                        <td class="text-end">{{ $suggestion['threshold'] }}</td>
                                        <td class="text-end"><strong>{{ $suggestion['suggested_quantity'] }}</strong></td>
                                        <td>
                                            <a href="{{ route('erp.stocks.adjust', $suggestion['product']) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-plus me-1"></i> Réapprovisionner
                                            </a>
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
        @endif

        {{-- Résumé --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">📊 Résumé</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-danger text-white rounded">
                                    <h3 class="mb-0">{{ isset($grouped['critical']) ? $grouped['critical']->count() : 0 }}</h3>
                                    <small>Critique</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-warning text-white rounded">
                                    <h3 class="mb-0">{{ isset($grouped['high']) ? $grouped['high']->count() : 0 }}</h3>
                                    <small>Élevée</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-info text-white rounded">
                                    <h3 class="mb-0">{{ isset($grouped['medium']) ? $grouped['medium']->count() : 0 }}</h3>
                                    <small>Moyenne</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-secondary text-white rounded">
                                    <h3 class="mb-0">{{ count($suggestions) }}</h3>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

