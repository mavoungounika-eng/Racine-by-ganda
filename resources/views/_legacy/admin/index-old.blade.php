@extends('layouts.admin')

@section('title', 'Gestion des Catégories')
@section('page-title', 'Gestion des Catégories')

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .premium-table {
        width: 100%;
    }
    
    .premium-table thead {
        background: linear-gradient(135deg, rgba(18, 8, 6, 0.8) 0%, rgba(22, 13, 12, 0.6) 100%);
    }
    
    .premium-table th {
        padding: 1.25rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border-bottom: 2px solid rgba(237, 95, 30, 0.2);
    }
    
    .premium-table td {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(212, 165, 116, 0.1);
        color: #e2e8f0;
    }
    
    .premium-table tbody tr:hover {
        background: rgba(237, 95, 30, 0.05);
        transform: scale(1.01);
    }
    
    .premium-input {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #e2e8f0;
        transition: all 0.3s;
    }
    
    .premium-input:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
    
    .premium-select {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #e2e8f0;
        transition: all 0.3s;
    }
    
    .premium-select:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-white" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-folder text-racine-orange mr-2"></i>
            Gestion des Catégories
        </h2>
        <a href="{{ route('admin.categories.create') }}"
           class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Nouvelle catégorie
        </a>
    </div>

    <!-- Filtres et recherche -->
    <div class="premium-card">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="grid md:grid-cols-4 gap-4">
            <input type="text"
                   name="search"
                   id="search"
                   value="{{ request('search') }}"
                   placeholder="Nom ou slug..."
                   class="premium-input">
            
            <select name="is_active" id="is_active" class="premium-select">
                <option value="">Tous les statuts</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactif</option>
            </select>

            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-search"></i>
                Filtrer
            </button>
            
            @if(request()->hasAny(['search', 'is_active']))
                <a href="{{ route('admin.categories.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Tableau des catégories -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ route('admin.categories.index', array_merge(request()->all(), ['sort_by' => 'name', 'sort_dir' => request('sort_dir') === 'asc' && request('sort_by') === 'name' ? 'desc' : 'asc'])) }}" class="hover:text-racine-orange transition">
                                Nom
                                @if(request('sort_by') === 'name')
                                    <span class="ml-1">{{ request('sort_dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th>Statut</th>
                        <th>Sous-catégories</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="font-semibold text-white">{{ $category->name }}</div>
                            @if($category->description)
                                <div class="text-xs text-slate-400 truncate max-w-xs mt-1">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="px-2 py-1 bg-slate-800 text-slate-300 rounded text-xs">{{ $category->slug }}</code>
                        </td>
                        <td>
                            @if($category->parent)
                                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-semibold">
                                    {{ $category->parent->name }}
                                </span>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        </td>
                        <td>
                            @if($category->is_active)
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">
                                    Actif
                                </span>
                            @else
                                <span class="px-3 py-1 bg-slate-700 text-slate-400 rounded-full text-xs font-semibold">
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td class="text-slate-300 font-semibold">{{ $category->children_count ?? 0 }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="h-9 w-9 rounded-lg bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-400 flex items-center justify-center transition hover:scale-110"
                                   title="Modifier">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <button type="button"
                                        onclick="openDeleteModal({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                        class="h-9 w-9 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-400 flex items-center justify-center transition hover:scale-110"
                                        title="Supprimer">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-folder-open text-5xl text-slate-600"></i>
                                <p class="text-slate-400 text-lg">Aucune catégorie trouvée</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
        <div class="px-6 py-4 border-t border-slate-700">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div id="deleteModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeDeleteModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-[#160D0C] border border-slate-700 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="bg-[#160D0C] px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-500/20 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-white" id="modal-title">
                                Confirmer la suppression
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-300">
                                    Êtes-vous sûr de vouloir supprimer la catégorie <strong id="categoryName" class="text-white"></strong> ?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-[#120806] px-6 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-700">
                    <button type="submit"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition">
                        Supprimer
                    </button>
                    <button type="button"
                            onclick="closeDeleteModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-700 shadow-sm px-4 py-2 bg-slate-800 text-base font-medium text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openDeleteModal(id, name) {
        document.getElementById('categoryName').textContent = name;
        document.getElementById('deleteForm').action = '{{ route('admin.categories.destroy', ':id') }}'.replace(':id', id);
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>
@endpush
@endsection
