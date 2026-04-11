@extends('layouts.admin')

@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des Utilisateurs')

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
    
    .premium-table tbody tr {
        transition: all 0.2s;
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
    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-users text-racine-orange mr-2"></i>
                Gestion des Utilisateurs
            </h2>
            <p class="text-slate-400">{{ $users->total() }} utilisateurs au total</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center gap-2">
            <i class="fas fa-user-plus"></i>
            Nouvel Utilisateur
        </a>
    </div>

    {{-- Filters --}}
    <div class="premium-card">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Rechercher..." 
                   class="premium-input">
            
            <select name="role" class="premium-select">
                <option value="">Tous les rôles</option>
                @foreach(\App\Models\Role::all() as $role)
                    <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-search"></i>
                Filtrer
            </button>

            @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('admin.users.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Users Table --}}
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Créé le</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="font-mono text-sm text-slate-400">#{{ $user->id }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-racine-orange to-racine-yellow flex items-center justify-center text-white font-semibold shadow-lg">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-white">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-slate-300">{{ $user->email }}</td>
                        <td>
                            @if($user->roleRelation)
                                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-semibold">
                                    {{ $user->roleRelation->name }}
                                </span>
                            @else
                                <span class="px-3 py-1 bg-slate-700 text-slate-400 rounded-full text-xs font-semibold">
                                    Aucun
                                </span>
                            @endif
                        </td>
                        <td class="text-slate-400 text-sm">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="h-9 w-9 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 flex items-center justify-center transition hover:scale-110"
                                   title="Modifier">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Êtes-vous sûr ?')"
                                            class="h-9 w-9 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-400 flex items-center justify-center transition hover:scale-110"
                                            title="Supprimer">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-users text-5xl text-slate-600"></i>
                                <p class="text-slate-400 text-lg">Aucun utilisateur trouvé</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-700">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
