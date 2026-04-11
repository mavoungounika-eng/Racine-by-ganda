@extends('layouts.admin')

@section('title', 'Détails de l\'Utilisateur')
@section('page-title', 'Détails de l\'Utilisateur')

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        margin-bottom: 1.5rem;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .info-item {
        padding: 1.5rem;
        background: rgba(18, 8, 6, 0.4);
        border-radius: 16px;
        border: 1px solid rgba(212, 165, 116, 0.1);
        transition: all 0.3s;
    }
    
    .info-item:hover {
        transform: translateY(-2px);
        border-color: rgba(212, 165, 116, 0.3);
    }
    
    .info-label {
        font-size: 0.875rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-user text-racine-orange mr-2"></i>
                Détails de l'utilisateur
            </h2>
            <p class="text-slate-400">Informations complètes sur l'utilisateur</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.users.edit', $user) }}"
               class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center gap-2">
                <i class="fas fa-edit"></i>
                Modifier
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    <div class="premium-card">
        <h3 class="text-xl font-bold text-white mb-6 pb-4 border-b border-slate-700" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-info-circle text-racine-orange mr-2"></i>
            Informations personnelles
        </h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nom complet</div>
                <div class="info-value">{{ $user->name }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $user->email }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Téléphone</div>
                <div class="info-value">{{ $user->phone ?? '—' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Statut</div>
                <div class="info-value">
                    @if($user->status === 'active')
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">Actif</span>
                    @elseif($user->status === 'inactive')
                        <span class="px-3 py-1 bg-slate-700 text-slate-400 rounded-full text-xs font-semibold">Inactif</span>
                    @else
                        <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold">Suspendu</span>
                    @endif
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Rôle</div>
                <div class="info-value">
                    @if($user->roleRelation)
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-semibold">
                            {{ $user->roleRelation->name }}
                        </span>
                    @else
                        <span class="px-3 py-1 bg-slate-700 text-slate-400 rounded-full text-xs font-semibold">Aucun</span>
                    @endif
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">ID Rôle</div>
                <div class="info-value text-slate-400">{{ $user->role_id ?? '—' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Email vérifié</div>
                <div class="info-value">
                    @if($user->email_verified_at)
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">
                            Oui ({{ $user->email_verified_at->format('d/m/Y H:i') }})
                        </span>
                    @else
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-semibold">
                            Non vérifié
                        </span>
                    @endif
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Date de création</div>
                <div class="info-value text-slate-300">{{ $user->created_at->format('d/m/Y à H:i') }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Dernière mise à jour</div>
                <div class="info-value text-slate-300">{{ $user->updated_at->format('d/m/Y à H:i') }}</div>
            </div>
        </div>
    </div>

    @if($user->id !== auth()->id())
    <div class="flex justify-end">
        <button type="button"
                onclick="openDeleteModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all flex items-center gap-2">
            <i class="fas fa-trash"></i>
            Supprimer
        </button>
    </div>
    @endif
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
                                    Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="userName" class="text-white"></strong> ? Cette action est irréversible.
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
    function openDeleteModal(userId, userName) {
        document.getElementById('userName').textContent = userName;
        document.getElementById('deleteForm').action = '{{ route('admin.users.destroy', ':id') }}'.replace(':id', userId);
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
