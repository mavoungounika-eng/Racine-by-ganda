@extends('layouts.admin-master')

@section('title', 'ERP - Gestion des Stocks')
@section('page-title', 'Gestion des Stocks')
@section('page-subtitle', 'Suivre et gérer les niveaux de stock')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-warehouse text-racine-orange me-2"></i>
            Gestion des Stocks
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Suivre et gérer les niveaux de stock de tous les produits
        </p>
    </div>
</div>

{{-- Statistiques rapides --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <a href="{{ route('erp.stocks.index') }}" 
           class="card card-racine text-decoration-none h-100 {{ !request('filter') ? 'border-primary border-2' : '' }}">
            <div class="card-body text-center py-3">
                <h4 class="mb-0 fw-bold text-racine-black">{{ $stats['total'] }}</h4>
                <small class="text-muted">Total</small>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('erp.stocks.index', ['filter' => 'ok']) }}" 
           class="card card-racine text-decoration-none h-100 {{ request('filter') === 'ok' ? 'bg-success text-white' : '' }}">
            <div class="card-body text-center py-3">
                <h4 class="mb-0 fw-bold {{ request('filter') === 'ok' ? 'text-white' : 'text-success' }}">{{ $stats['ok'] }}</h4>
                <small class="{{ request('filter') === 'ok' ? 'text-white' : 'text-muted' }}">OK</small>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('erp.stocks.index', ['filter' => 'low']) }}" 
           class="card card-racine text-decoration-none h-100 {{ request('filter') === 'low' ? 'bg-warning' : '' }}">
            <div class="card-body text-center py-3">
                <h4 class="mb-0 fw-bold {{ request('filter') === 'low' ? 'text-dark' : 'text-warning' }}">{{ $stats['low'] }}</h4>
                <small class="text-muted">Faible</small>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('erp.stocks.index', ['filter' => 'out']) }}" 
           class="card card-racine text-decoration-none h-100 {{ request('filter') === 'out' ? 'bg-danger text-white' : '' }}">
            <div class="card-body text-center py-3">
                <h4 class="mb-0 fw-bold {{ request('filter') === 'out' ? 'text-white' : 'text-danger' }}">{{ $stats['out'] }}</h4>
                <small class="{{ request('filter') === 'out' ? 'text-white' : 'text-muted' }}">Rupture</small>
            </div>
        </a>
    </div>
</div>

{{-- Barre de recherche --}}
@include('partials.admin.filter-bar', [
    'route' => route('erp.stocks.index'),
    'search' => true,
    'filters' => []
])

{{-- Tableau des produits --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-hashtag me-2"></i>ID
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-box me-2"></i>Produit
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-money-bill me-2"></i>Prix
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-warehouse me-2"></i>Stock
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-2"></i>Statut
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="fw-bold text-racine-black">#{{ $product->id }}</span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">{{ Str::limit($product->title, 40) }}</div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="fw-bold text-racine-orange">
                                {{ number_format($product->price, 0, ',', ' ') }} FCFA
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="fw-bold {{ $product->stock <= 0 ? 'text-danger' : ($product->stock < 5 ? 'text-warning' : 'text-success') }}">
                                {{ $product->stock }}
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($product->stock <= 0)
                                <span class="badge bg-danger rounded-pill">
                                    <i class="fas fa-times-circle me-1"></i>Rupture
                                </span>
                            @elseif($product->stock < 5)
                                <span class="badge bg-warning text-dark rounded-pill">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Faible
                                </span>
                            @else
                                <span class="badge bg-success rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i>OK
                                </span>
                            @endif
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('erp.stocks.adjust', $product) }}" 
                                   class="btn btn-sm btn-outline-success"
                                   title="Ajuster le stock">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-md-inline ms-1">Ajuster</span>
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier le produit">
                                    <i class="fas fa-cog"></i>
                                    <span class="d-none d-md-inline ms-1">Modifier</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucun produit trouvé</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($products->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $products->firstItem() ?? 0 }} à {{ $products->lastItem() ?? 0 }} sur {{ $products->total() }} résultats
                </div>
                <div>
                    {{ $products->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

