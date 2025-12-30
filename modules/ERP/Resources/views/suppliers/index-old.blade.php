@extends('layouts.admin-master')

@section('title', 'ERP - Fournisseurs')
@section('page-title', 'Fournisseurs')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Fournisseurs</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">🏭 Fournisseurs</h1>
                    <a href="{{ route('erp.suppliers.create') }}" class="btn btn-primary">
                        + Nouveau Fournisseur
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
                <form method="GET" action="{{ route('erp.suppliers.index') }}" class="row align-items-center">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher un fournisseur..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <select name="status" class="form-control">
                            <option value="">Tous les statuts</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Liste --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($suppliers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Nom</th>
                                    <th class="border-0">Email</th>
                                    <th class="border-0">Téléphone</th>
                                    <th class="border-0">Statut</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suppliers as $supplier)
                                <tr>
                                    <td class="align-middle">
                                        <strong>{{ $supplier->name }}</strong>
                                    </td>
                                    <td class="align-middle">{{ $supplier->email ?? '-' }}</td>
                                    <td class="align-middle">{{ $supplier->phone ?? '-' }}</td>
                                    <td class="align-middle">
                                        @if($supplier->is_active)
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('erp.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary mr-1">
                                            Modifier
                                        </a>
                                        <form action="{{ route('erp.suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce fournisseur ?')">
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
                        {{ $suppliers->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="display-1">🏭</span>
                        <h5 class="text-muted mt-3">Aucun fournisseur enregistré</h5>
                        <a href="{{ route('erp.suppliers.create') }}" class="btn btn-primary mt-3">
                            + Ajouter un fournisseur
                        </a>
                    </div>
                @endif
            </div>
        </div>
</div>
@endsection

