@extends('layouts.admin-master')

@section('title', 'Gestion des Catégories')
@section('page-title', 'Gestion des Catégories')
@section('page-subtitle', 'Organiser vos catégories de produits')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-folder text-racine-orange me-2"></i>
            Gestion des Catégories
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Organiser vos catégories de produits
        </p>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-racine-orange">
        <i class="fas fa-plus me-2"></i>
        Nouvelle Catégorie
    </a>
</div>

{{-- Barre de filtres --}}
@include('partials.admin.filter-bar', [
    'route' => route('admin.categories.index'),
    'search' => true,
    'filters' => [
        [
            'name' => 'is_active',
            'label' => 'Statut',
            'type' => 'select',
            'icon' => 'fas fa-toggle-on',
            'width' => 3,
            'options' => [
                ['value' => '', 'label' => 'Tous les statuts'],
                ['value' => '1', 'label' => 'Actives'],
                ['value' => '0', 'label' => 'Inactives']
            ]
        ]
    ]
])

{{-- Tableau des catégories --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-tag me-2"></i>Nom
                            <a href="{{ route('admin.categories.index', array_merge(request()->all(), ['sort_by' => 'name', 'sort_dir' => request('sort_dir') === 'asc' && request('sort_by') === 'name' ? 'desc' : 'asc'])) }}" 
                               class="text-muted ms-2" 
                               title="Trier">
                                <i class="fas fa-sort{{ request('sort_by') === 'name' ? (request('sort_dir') === 'asc' ? '-up' : '-down') : '' }}"></i>
                            </a>
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-link me-2"></i>Slug
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-sitemap me-2"></i>Parent
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-toggle-on me-2"></i>Statut
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-folder-open me-2"></i>Sous-catégories
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">{{ $category->name }}</div>
                            @if($category->description)
                                <div class="small text-muted mt-1" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $category->description }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <code class="text-racine-orange small">{{ $category->slug }}</code>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($category->parent)
                                <span class="badge bg-info text-white">
                                    <i class="fas fa-level-up-alt me-1"></i>
                                    {{ $category->parent->name }}
                                </span>
                            @else
                                <span class="text-muted">
                                    <i class="fas fa-home me-1"></i>
                                    Racine
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($category->is_active)
                                <span class="badge bg-success rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-pause-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-folder me-1"></i>
                                {{ $category->children_count ?? 0 }}
                            </span>
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.categories.edit', $category) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-md-inline ms-1">Modifier</span>
                                </a>
                                <button type="button"
                                        onclick="openDeleteModal({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                    <span class="d-none d-md-inline ms-1">Supprimer</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucune catégorie trouvée</p>
                                <a href="{{ route('admin.categories.create') }}" class="btn btn-racine-orange">
                                    <i class="fas fa-plus me-2"></i>
                                    Créer votre première catégorie
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($categories->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $categories->firstItem() ?? 0 }} à {{ $categories->lastItem() ?? 0 }} sur {{ $categories->total() }} résultats
                </div>
                <div>
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p class="mb-0">
                        Êtes-vous sûr de vouloir supprimer la catégorie <strong id="categoryName" class="text-racine-black"></strong> ?
                    </p>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Cette action supprimera également toutes les sous-catégories associées.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        Supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openDeleteModal(id, name) {
    document.getElementById('categoryName').textContent = name;
    document.getElementById('deleteForm').action = '{{ route('admin.categories.destroy', ':id') }}'.replace(':id', id);
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush

