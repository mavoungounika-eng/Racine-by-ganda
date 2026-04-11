@extends('layouts.admin-master')

@section('title', 'ERP - Matières Premières')
@section('page-title', 'Matières Premières')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Matières Premières</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">🧵 Matières Premières</h1>
                    <a href="{{ route('erp.materials.create') }}" class="btn btn-primary">
                        + Nouvelle Matière
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        {{-- Recherche --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('erp.materials.index') }}" class="row align-items-center">
                    <div class="col-md-9 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher par nom ou SKU..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block">Rechercher</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Liste --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($materials->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">SKU</th>
                                    <th class="border-0">Nom</th>
                                    <th class="border-0">Fournisseur</th>
                                    <th class="border-0">Unité</th>
                                    <th class="border-0">Prix Unitaire</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materials as $material)
                                <tr>
                                    <td class="align-middle">
                                        <code>{{ $material->sku ?? '-' }}</code>
                                    </td>
                                    <td class="align-middle">
                                        <strong>{{ $material->name }}</strong>
                                    </td>
                                    <td class="align-middle">
                                        {{ $material->supplier->name ?? '-' }}
                                    </td>
                                    <td class="align-middle">{{ $material->unit ?? '-' }}</td>
                                    <td class="align-middle">
                                        @if($material->unit_price)
                                            {{ number_format($material->unit_price, 0, ',', ' ') }} FCFA
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('erp.materials.edit', $material) }}" class="btn btn-sm btn-outline-primary mr-1">
                                            Modifier
                                        </a>
                                        <form action="{{ route('erp.materials.destroy', $material) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette matière ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-3">
                        {{ $materials->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="display-1">🧵</span>
                        <h5 class="text-muted mt-3">Aucune matière première enregistrée</h5>
                        <a href="{{ route('erp.materials.create') }}" class="btn btn-primary mt-3">
                            + Ajouter une matière
                        </a>
                    </div>
                @endif
            </div>
        </div>
</div>
@endsection

