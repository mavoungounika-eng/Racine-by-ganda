@extends('layouts.admin-master')

@section('title', 'Rapport de Valorisation du Stock - ERP')
@section('page-title', 'Rapport de Valorisation du Stock')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="h3 mb-1">💰 Valorisation du Stock</h2>
                    <p class="text-muted mb-0">Rapport généré le {{ now()->format('d/m/Y à H:i') }}</p>
                </div>
                <div>
                    <a href="{{ route('erp.reports.stock-valuation', ['format' => 'json']) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Export JSON
                    </a>
                    <button type="button" class="btn btn-primary btn-sm btn-print-trigger">
                        <i class="fas fa-print me-1"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Résumé Global --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Produits Finis</h6>
                    <h3 class="mb-0">{{ number_format($totalProductsValue, 0, ',', ' ') }} XAF</h3>
                    <small class="text-white-50">{{ $productsValuation->count() }} produits</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Matières Premières</h6>
                    <h3 class="mb-0">{{ number_format($totalMaterialsValue, 0, ',', ' ') }} XAF</h3>
                    <small class="text-white-50">{{ $materialsValuation->count() }} matières</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Valorisation</h6>
                    <h3 class="mb-0">{{ number_format($totalStockValue, 0, ',', ' ') }} XAF</h3>
                    <small class="text-white-50">Valeur totale du stock</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Produits Finis --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">📦 Produits Finis</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Produit</th>
                                    <th class="border-0 text-end">Prix Unitaire</th>
                                    <th class="border-0 text-end">Stock</th>
                                    <th class="border-0 text-end">Valeur Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productsValuation as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td><strong>{{ $product->title }}</strong></td>
                                    <td class="text-end">{{ number_format($product->price, 0, ',', ' ') }} XAF</td>
                                    <td class="text-end">{{ $product->stock }}</td>
                                    <td class="text-end"><strong>{{ number_format($product->total_value, 0, ',', ' ') }} XAF</strong></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucun produit en stock</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-end">Total Produits Finis :</th>
                                    <th class="text-end">{{ number_format($totalProductsValue, 0, ',', ' ') }} XAF</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Matières Premières --}}
    @if($materialsValuation->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">🧵 Matières Premières</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Matière</th>
                                    <th class="border-0">Unité</th>
                                    <th class="border-0 text-end">Stock</th>
                                    <th class="border-0 text-end">Prix Moyen</th>
                                    <th class="border-0 text-end">Valeur Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materialsValuation as $item)
                                <tr>
                                    <td><strong>{{ $item['material']->name }}</strong></td>
                                    <td>{{ $item['material']->unit ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item['stock'], 2, ',', ' ') }}</td>
                                    <td class="text-end">{{ number_format($item['avg_price'], 0, ',', ' ') }} XAF</td>
                                    <td class="text-end"><strong>{{ number_format($item['total_value'], 0, ',', ' ') }} XAF</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-end">Total Matières Premières :</th>
                                    <th class="text-end">{{ number_format($totalMaterialsValue, 0, ',', ' ') }} XAF</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <a href="{{ route('erp.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour au Dashboard
                </a>
                <div>
                    <a href="{{ route('erp.reports.stock-valuation', ['format' => 'json']) }}" class="btn btn-outline-primary">
                        <i class="fas fa-download me-1"></i> Export JSON
                    </a>
                    <button type="button" class="btn btn-primary btn-print-trigger">
                        <i class="fas fa-print me-1"></i> Imprimer / PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style nonce="{{ csp_nonce() }}">
    @media print {
        .btn, .card-header .btn { display: none !important; }
        .card { border: 1px solid #ddd !important; page-break-inside: avoid; }
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
document.querySelectorAll('.btn-print-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () { window.print(); });
});
</script>
@endpush
@endsection

