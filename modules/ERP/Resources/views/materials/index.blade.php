@extends('layouts.admin-master')

@section('title', 'ERP - Matières Premières')
@section('page-title', 'Matières Premières')
@section('page-subtitle', 'Gérer vos matières premières et composants')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-cube text-racine-orange me-2"></i>
            Matières Premières
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Gérer vos matières premières et composants
        </p>
    </div>
    <a href="{{ route('erp.materials.create') }}" class="btn btn-racine-orange">
        <i class="fas fa-plus me-2"></i>
        Nouvelle Matière
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Barre de recherche --}}
@include('partials.admin.filter-bar', [
    'route' => route('erp.materials.index'),
    'search' => true,
    'filters' => []
])

{{-- Tableau des matières premières --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-barcode me-2"></i>SKU
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-tag me-2"></i>Nom
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-truck me-2"></i>Fournisseur
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-ruler me-2"></i>Unité
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-money-bill me-2"></i>Prix Unitaire
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            @if($material->sku)
                                <code class="text-racine-orange">{{ $material->sku }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">{{ $material->name }}</div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($material->supplier)
                                <span class="badge bg-light text-dark">
                                    {{ $material->supplier->name }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="text-muted">{{ $material->unit ?? '-' }}</span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($material->unit_price)
                                <span class="fw-bold text-racine-orange">
                                    {{ number_format($material->unit_price, 0, ',', ' ') }} FCFA
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('erp.materials.edit', $material) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-md-inline ms-1">Modifier</span>
                                </a>
                                <form action="{{ route('erp.materials.destroy', $material) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette matière première ?');">
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
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-cube fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucune matière première enregistrée</p>
                                <a href="{{ route('erp.materials.create') }}" class="btn btn-racine-orange">
                                    <i class="fas fa-plus me-2"></i>
                                    Ajouter une matière
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($materials->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $materials->firstItem() ?? 0 }} à {{ $materials->lastItem() ?? 0 }} sur {{ $materials->total() }} résultats
                </div>
                <div>
                    {{ $materials->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

