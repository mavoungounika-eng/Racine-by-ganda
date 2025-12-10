@extends('layouts.admin-master')

@section('title', 'ERP - Gestion des Stocks')
@section('page-title', 'Gestion des Stocks')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Stocks</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-0">📦 Gestion des Stocks</h1>
            </div>
        </div>

        {{-- Stats rapides --}}
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('erp.stocks.index') }}" class="card border-0 shadow-sm text-decoration-none {{ !request('filter') ? 'border-primary' : '' }}" style="{{ !request('filter') ? 'border: 2px solid #007bff !important;' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0">{{ $stats['total'] }}</h4>
                        <small class="text-muted">Total</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('erp.stocks.index', ['filter' => 'ok']) }}" class="card border-0 shadow-sm text-decoration-none {{ request('filter') === 'ok' ? 'bg-success text-white' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 {{ request('filter') === 'ok' ? '' : 'text-success' }}">{{ $stats['ok'] }}</h4>
                        <small class="{{ request('filter') === 'ok' ? 'text-white' : 'text-muted' }}">OK</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('erp.stocks.index', ['filter' => 'low']) }}" class="card border-0 shadow-sm text-decoration-none {{ request('filter') === 'low' ? 'bg-warning' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 {{ request('filter') === 'low' ? '' : 'text-warning' }}">{{ $stats['low'] }}</h4>
                        <small class="text-muted">Faible</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('erp.stocks.index', ['filter' => 'out']) }}" class="card border-0 shadow-sm text-decoration-none {{ request('filter') === 'out' ? 'bg-danger text-white' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 {{ request('filter') === 'out' ? '' : 'text-danger' }}">{{ $stats['out'] }}</h4>
                        <small class="{{ request('filter') === 'out' ? 'text-white' : 'text-muted' }}">Rupture</small>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recherche --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('erp.stocks.index') }}" class="row align-items-center">
                    @if(request('filter'))
                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                    @endif
                    <div class="col-md-8 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher un produit..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-block">Rechercher</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Liste des produits --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Produit</th>
                                    <th class="border-0">Prix</th>
                                    <th class="border-0">Stock</th>
                                    <th class="border-0">Statut</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                <tr>
                                    <td class="align-middle">{{ $product->id }}</td>
                                    <td class="align-middle">
                                        <strong>{{ Str::limit($product->title, 40) }}</strong>
                                    </td>
                                    <td class="align-middle">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
                                    <td class="align-middle">
                                        <span class="font-weight-bold {{ $product->stock <= 0 ? 'text-danger' : ($product->stock < 5 ? 'text-warning' : 'text-success') }}">
                                            {{ $product->stock }}
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        @if($product->stock <= 0)
                                            <span class="badge bg-danger">Rupture</span>
                                        @elseif($product->stock < 5)
                                            <span class="badge bg-warning">Faible</span>
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('erp.stocks.adjust', $product) }}" class="btn btn-sm btn-outline-success mr-1">
                                            📊 Ajuster
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                            Modifier
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-3">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="display-1">📦</span>
                        <h5 class="text-muted mt-3">Aucun produit trouvé</h5>
                    </div>
                @endif
            </div>
        </div>
</div>
@endsection

