@extends('layouts.admin')

@section('title', 'Alertes de Stock - Admin')
@section('page-title', 'Alertes de Stock')

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .stat-card-premium {
        background: linear-gradient(135deg, rgba(22, 13, 12, 0.8) 0%, rgba(18, 8, 6, 0.6) 100%);
        border: 1px solid rgba(212, 165, 116, 0.15);
        border-radius: 20px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .stat-card-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--stat-color-1), var(--stat-color-2));
    }
    
    .stat-card-premium:hover {
        transform: translateY(-4px);
        border-color: rgba(212, 165, 116, 0.3);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.4);
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
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <h2 class="text-2xl font-bold text-white" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
            Alertes de Stock
        </h2>
        <div class="flex gap-3">
            <form action="{{ route('admin.stock-alerts.resolve-all') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-green-500/30 transition-all flex items-center gap-2">
                    <i class="fas fa-check-double"></i>
                    Résoudre toutes les alertes
                </button>
            </form>
            <a href="{{ route('admin.products.index') }}"
               class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Retour aux produits
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid md:grid-cols-4 gap-6">
        <div class="stat-card-premium" style="--stat-color-1: #F59E0B; --stat-color-2: #D97706;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-2 font-semibold">Actives</p>
                    <p class="text-4xl font-bold text-yellow-400" style="font-family: 'Playfair Display', serif;">{{ $stats['active'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card-premium" style="--stat-color-1: #22C55E; --stat-color-2: #16A34A;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-2 font-semibold">Résolues</p>
                    <p class="text-4xl font-bold text-green-400" style="font-family: 'Playfair Display', serif;">{{ $stats['resolved'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card-premium" style="--stat-color-1: #64748B; --stat-color-2: #475569;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-2 font-semibold">Ignorées</p>
                    <p class="text-4xl font-bold text-slate-400" style="font-family: 'Playfair Display', serif;">{{ $stats['dismissed'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center shadow-lg">
                    <i class="fas fa-ban text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card-premium" style="--stat-color-1: #3B82F6; --stat-color-2: #2563EB;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-2 font-semibold">Total</p>
                    <p class="text-4xl font-bold text-blue-400" style="font-family: 'Playfair Display', serif;">{{ $stats['total'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-list text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="premium-card">
        <form method="GET" action="{{ route('admin.stock-alerts.index') }}" class="grid md:grid-cols-4 gap-4">
            <select name="status" id="status" class="premium-select">
                <option value="">Tous les statuts</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolues</option>
                <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>Ignorées</option>
            </select>
            
            <input type="text"
                   name="search"
                   id="search"
                   class="premium-input"
                   placeholder="Nom du produit..."
                   value="{{ request('search') }}">
            
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-search"></i>
                Filtrer
            </button>
            
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.stock-alerts.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Liste des alertes -->
    <div class="premium-card overflow-hidden">
        @if($alerts->count() > 0)
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Stock actuel</th>
                        <th>Seuil</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                    <tr>
                        <td>
                            <div class="font-semibold text-white">{{ $alert->product->title ?? 'Produit supprimé' }}</div>
                            @if($alert->product)
                                <div class="text-xs text-slate-400 mt-1">ID: {{ $alert->product->id }}</div>
                            @endif
                        </td>
                        <td>
                            @if($alert->current_stock <= 0)
                                <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold">
                                    {{ $alert->current_stock }} unités
                                </span>
                            @else
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-semibold">
                                    {{ $alert->current_stock }} unités
                                </span>
                            @endif
                        </td>
                        <td class="text-slate-300 font-semibold">{{ $alert->threshold }} unités</td>
                        <td>
                            @if($alert->status === 'active')
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-semibold">Active</span>
                            @elseif($alert->status === 'resolved')
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">Résolue</span>
                            @else
                                <span class="px-3 py-1 bg-slate-700 text-slate-400 rounded-full text-xs font-semibold">Ignorée</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-sm text-slate-300">{{ $alert->created_at->format('d/m/Y H:i') }}</div>
                            @if($alert->resolved_at)
                                <div class="text-xs text-slate-500 mt-1">Résolue: {{ $alert->resolved_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td>
                            @if($alert->status === 'active')
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.stock-alerts.resolve', $alert) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="h-9 w-9 rounded-lg bg-green-500/20 hover:bg-green-500/30 text-green-400 flex items-center justify-center transition hover:scale-110"
                                            title="Résoudre">
                                        <i class="fas fa-check text-sm"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.stock-alerts.dismiss', $alert) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="h-9 w-9 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-400 flex items-center justify-center transition hover:scale-110"
                                            title="Ignorer">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-slate-500">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-700">
            {{ $alerts->links() }}
        </div>
        @else
        <div class="py-16 text-center">
            <div class="flex flex-col items-center gap-4">
                <div class="h-24 w-24 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-4xl"></i>
                </div>
                <h4 class="text-xl font-bold text-white">Aucune alerte</h4>
                <p class="text-slate-400">Tous les stocks sont suffisants.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
