@extends('layouts.admin')

@section('title', 'Gestion des Commandes')
@section('page-title', 'Gestion des Commandes')

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
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-white" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-shopping-cart text-racine-orange mr-2"></i>
            Gestion des Commandes
        </h2>
    </div>

    <!-- Filtres -->
    <div class="premium-card">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid md:grid-cols-4 gap-4">
            <input type="text"
                   name="search"
                   id="search"
                   value="{{ request('search') }}"
                   placeholder="Nom, Email ou ID..."
                   class="premium-input">
            
            <select name="status" id="status" class="premium-select">
                <option value="">Tous les statuts</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payée</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Expédiée</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminée</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
            </select>

            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-search"></i>
                Filtrer
            </button>
            
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.orders.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Tableau des commandes -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="font-semibold text-white">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <div class="font-semibold text-white">{{ $order->customer_name ?? $order->user?->name ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400">{{ $order->customer_email ?? $order->user?->email ?? '' }}</div>
                        </td>
                        <td>
                            <p class="font-bold text-racine-orange">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} F</p>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => ['bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-400', 'icon' => 'fa-clock'],
                                    'paid' => ['bg' => 'bg-blue-500/20', 'text' => 'text-blue-400', 'icon' => 'fa-check-circle'],
                                    'shipped' => ['bg' => 'bg-purple-500/20', 'text' => 'text-purple-400', 'icon' => 'fa-shipping-fast'],
                                    'completed' => ['bg' => 'bg-green-500/20', 'text' => 'text-green-400', 'icon' => 'fa-check-double'],
                                    'cancelled' => ['bg' => 'bg-red-500/20', 'text' => 'text-red-400', 'icon' => 'fa-times-circle'],
                                ];
                                $status = $statusColors[$order->status] ?? $statusColors['pending'];
                            @endphp
                            <span class="px-3 py-1 {{ $status['bg'] }} {{ $status['text'] }} rounded-full text-xs font-semibold inline-flex items-center gap-1">
                                <i class="fas {{ $status['icon'] }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="text-slate-400 text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex items-center justify-end">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="px-4 py-2 bg-racine-orange/20 hover:bg-racine-orange/30 text-racine-orange rounded-lg font-semibold transition hover:scale-105">
                                    Voir
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-shopping-cart text-5xl text-slate-600"></i>
                                <p class="text-slate-400 text-lg">Aucune commande trouvée</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-slate-700">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
