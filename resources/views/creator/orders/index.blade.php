@extends('layouts.creator')

@section('title', 'Mes Commandes - RACINE BY GANDA')
@section('page-title', 'Mes Commandes')

@push('styles')
<style>
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
        transition: all 0.3s ease;
    }
    
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
        border-color: rgba(212, 165, 116, 0.2);
    }
    
    .stat-card-premium {
        background: linear-gradient(135deg, white 0%, #faf8f5 100%);
        border-radius: 20px;
        padding: 1.75rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(212, 165, 116, 0.15);
        position: relative;
        overflow: hidden;
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
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .premium-table {
        width: 100%;
    }
    
    .premium-table thead {
        background: linear-gradient(135deg, #F8F6F3 0%, #E5DDD3 100%);
    }
    
    .premium-table th {
        padding: 1.25rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.75rem;
        color: #8B7355;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border-bottom: 2px solid #D4A574;
    }
    
    .premium-table td {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid #F8F6F3;
        color: #2C1810;
    }
    
    .premium-table tbody tr {
        transition: all 0.2s;
    }
    
    .premium-table tbody tr:hover {
        background: linear-gradient(90deg, rgba(212, 165, 116, 0.05) 0%, transparent 100%);
        transform: scale(1.01);
    }
    
    .badge-premium {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .premium-select {
        background: white;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #2C1810;
        transition: all 0.3s;
    }
    
    .premium-select:focus {
        outline: none;
        border-color: #D4A574;
        box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.1);
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .action-btn-premium {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-center;
        transition: all 0.3s;
        border: 1px solid transparent;
    }
    
    .action-btn-premium:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Stats rapides --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="stat-card-premium" style="--stat-color-1: #ED5F1E; --stat-color-2: #FFB800;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Total</p>
                    <p class="text-3xl font-bold text-[#ED5F1E]" style="font-family: 'Playfair Display', serif;">{{ $stats['total'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#ED5F1E] to-[#FFB800] flex items-center justify-center shadow-lg">
                    <i class="fas fa-shopping-cart text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-premium" style="--stat-color-1: #F59E0B; --stat-color-2: #D97706;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">En attente</p>
                    <p class="text-3xl font-bold text-[#F59E0B]" style="font-family: 'Playfair Display', serif;">{{ $stats['pending'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#F59E0B] to-[#D97706] flex items-center justify-center shadow-lg">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-premium" style="--stat-color-1: #3B82F6; --stat-color-2: #2563EB;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Payées</p>
                    <p class="text-3xl font-bold text-[#3B82F6]" style="font-family: 'Playfair Display', serif;">{{ $stats['paid'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#3B82F6] to-[#2563EB] flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-premium" style="--stat-color-1: #8B5CF6; --stat-color-2: #7C3AED;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Expédiées</p>
                    <p class="text-3xl font-bold text-[#8B5CF6]" style="font-family: 'Playfair Display', serif;">{{ $stats['shipped'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#8B5CF6] to-[#7C3AED] flex items-center justify-center shadow-lg">
                    <i class="fas fa-shipping-fast text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-premium" style="--stat-color-1: #22C55E; --stat-color-2: #16A34A;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Terminées</p>
                    <p class="text-3xl font-bold text-[#22C55E]" style="font-family: 'Playfair Display', serif;">{{ $stats['completed'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#22C55E] to-[#16A34A] flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-double text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="premium-card mb-8">
        <form method="GET" class="flex items-center gap-4 flex-wrap">
            <label class="text-sm font-semibold text-[#2C1810]">Filtrer par statut:</label>
            <select name="status" class="premium-select">
                <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Tous les statuts</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payées</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Expédiées</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminées</option>
            </select>
            
            <button type="submit" class="premium-btn">
                <i class="fas fa-filter"></i>
                Filtrer
            </button>
            
            @if(request()->has('status'))
                <a href="{{ route('creator.orders.index') }}" class="premium-btn" style="background: linear-gradient(135deg, #64748B 0%, #475569 100%);">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Liste des commandes --}}
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <p class="font-bold text-[#2C1810]">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </td>
                        <td>
                            <p class="text-[#8B7355] font-medium">{{ $order->created_at->format('d/m/Y') }}</p>
                            <p class="text-sm text-[#8B7355]">{{ $order->created_at->format('H:i') }}</p>
                        </td>
                        <td>
                            <p class="font-semibold text-[#2C1810]">{{ $order->customer_name ?? $order->user?->name ?? 'N/A' }}</p>
                            <p class="text-sm text-[#8B7355]">{{ $order->customer_email ?? $order->user?->email ?? '' }}</p>
                        </td>
                        <td>
                            <p class="font-bold text-[#ED5F1E] text-lg">{{ number_format($order->creator_total ?? 0, 0, ',', ' ') }} F</p>
                            <p class="text-xs text-[#8B7355]">Vos produits</p>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => ['bg' => 'rgba(234, 179, 8, 0.1)', 'text' => '#F59E0B', 'border' => 'rgba(234, 179, 8, 0.2)', 'icon' => 'fa-clock'],
                                    'paid' => ['bg' => 'rgba(59, 130, 246, 0.1)', 'text' => '#3B82F6', 'border' => 'rgba(59, 130, 246, 0.2)', 'icon' => 'fa-check-circle'],
                                    'in_production' => ['bg' => 'rgba(139, 92, 246, 0.1)', 'text' => '#8B5CF6', 'border' => 'rgba(139, 92, 246, 0.2)', 'icon' => 'fa-cog'],
                                    'ready_to_ship' => ['bg' => 'rgba(99, 102, 241, 0.1)', 'text' => '#6366F1', 'border' => 'rgba(99, 102, 241, 0.2)', 'icon' => 'fa-box'],
                                    'shipped' => ['bg' => 'rgba(139, 92, 246, 0.1)', 'text' => '#8B5CF6', 'border' => 'rgba(139, 92, 246, 0.2)', 'icon' => 'fa-shipping-fast'],
                                    'completed' => ['bg' => 'rgba(34, 197, 94, 0.1)', 'text' => '#22C55E', 'border' => 'rgba(34, 197, 94, 0.2)', 'icon' => 'fa-check-double'],
                                    'cancelled' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'text' => '#EF4444', 'border' => 'rgba(239, 68, 68, 0.2)', 'icon' => 'fa-times-circle'],
                                ];
                                $status = $statusColors[$order->status] ?? $statusColors['pending'];
                            @endphp
                            <span class="badge-premium" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }}; border: 1px solid {{ $status['border'] }};">
                                <i class="fas {{ $status['icon'] }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('creator.orders.show', $order) }}" 
                                   class="action-btn-premium"
                                   style="background: rgba(59, 130, 246, 0.1); color: #3B82F6; border-color: rgba(59, 130, 246, 0.2);"
                                   title="Voir les détails">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-shopping-cart text-5xl text-[#8B7355]"></i>
                                <p class="text-xl font-bold text-[#2C1810]">Aucune commande trouvée</p>
                                <p class="text-[#8B7355]">Vos commandes apparaîtront ici</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-[#E5DDD3] bg-gradient-to-r from-[#F8F6F3] to-white">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
