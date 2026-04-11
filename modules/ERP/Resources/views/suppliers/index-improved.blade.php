@extends('layouts.admin-master')

@section('title', 'ERP - Fournisseurs')
@section('page-title', 'Fournisseurs')
@section('page-subtitle', 'Gérer vos fournisseurs et partenaires')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-truck text-racine-orange me-2"></i>
            Fournisseurs
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Gérer vos fournisseurs et partenaires
        </p>
    </div>
    <a href="{{ route('erp.suppliers.create') }}" class="btn btn-racine-orange">
        <i class="fas fa-plus me-2"></i>
        Nouveau Fournisseur
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Barre de filtres --}}
@include('partials.admin.filter-bar', [
    'route' => route('erp.suppliers.index'),
    'search' => true,
    'filters' => [
        [
            'name' => 'status',
            'label' => 'Statut',
            'type' => 'select',
            'icon' => 'fas fa-toggle-on',
            'width' => 3,
            'options' => [
                ['value' => '', 'label' => 'Tous les statuts'],
                ['value' => 'active', 'label' => 'Actifs'],
                ['value' => 'inactive', 'label' => 'Inactifs']
            ]
        ]
    ]
])

{{-- Tableau des fournisseurs --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-building me-2"></i>Nom
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-envelope me-2"></i>Email
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-phone me-2"></i>Téléphone
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-toggle-on me-2"></i>Statut
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">{{ $supplier->name }}</div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($supplier->email)
                                <div class="text-muted">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ $supplier->email }}
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($supplier->phone)
                                <div class="text-muted">
                                    <i class="fas fa-phone me-1"></i>
                                    {{ $supplier->phone }}
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($supplier->is_active)
                                <span class="badge bg-success rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i>Actif
                                </span>
                            @else
                                <span class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-pause-circle me-1"></i>Inactif
                                </span>
                            @endif
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('erp.suppliers.edit', $supplier) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-md-inline ms-1">Modifier</span>
                                </a>
                                <form action="{{ route('erp.suppliers.destroy', $supplier) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-sm btn-outline-danger"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                        <span class="d-none d-md-inline ms-1">Supprimer</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-truck fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucun fournisseur enregistré</p>
                                <a href="{{ route('erp.suppliers.create') }}" class="btn btn-racine-orange">
                                    <i class="fas fa-plus me-2"></i>
                                    Ajouter un fournisseur
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($suppliers->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $suppliers->firstItem() ?? 0 }} à {{ $suppliers->lastItem() ?? 0 }} sur {{ $suppliers->total() }} résultats
                </div>
                <div>
                    {{ $suppliers->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

