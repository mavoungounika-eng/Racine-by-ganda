@extends('layouts.creator')

@section('title', 'Mes Finances - RACINE BY GANDA')
@section('page-title', 'Mes Finances')

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
        padding: 2rem;
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
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Filtre période --}}
    <div class="premium-card mb-8">
        <form method="GET" class="flex items-center gap-4">
            <label class="text-sm font-semibold text-[#2C1810]">Période:</label>
            <select name="period" 
                    onchange="this.form.submit()"
                    class="premium-select">
                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Toutes les périodes</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Ce mois-ci</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Cette année</option>
            </select>
        </form>
    </div>

    {{-- Cartes récapitulatives --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Chiffre d'affaires brut --}}
        <div class="stat-card-premium" style="--stat-color-1: #ED5F1E; --stat-color-2: #FFB800;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Chiffre d'affaires brut</p>
                    <p class="text-4xl font-bold text-[#ED5F1E]" style="font-family: 'Playfair Display', serif;">{{ number_format($grossRevenue, 0, ',', ' ') }}</p>
                    <p class="text-sm text-[#8B7355] mt-1">FCFA</p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-[#ED5F1E] to-[#FFB800] flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-line text-white text-3xl"></i>
                </div>
            </div>
            <p class="text-xs text-[#8B7355]">Total des ventes (période sélectionnée)</p>
        </div>

        {{-- Commission RACINE --}}
        <div class="stat-card-premium" style="--stat-color-1: #F59E0B; --stat-color-2: #D97706;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Commission RACINE (20%)</p>
                    <p class="text-4xl font-bold text-[#F59E0B]" style="font-family: 'Playfair Display', serif;">{{ number_format($commission, 0, ',', ' ') }}</p>
                    <p class="text-sm text-[#8B7355] mt-1">FCFA</p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-[#F59E0B] to-[#D97706] flex items-center justify-center shadow-lg">
                    <i class="fas fa-percent text-white text-3xl"></i>
                </div>
            </div>
            <p class="text-xs text-[#8B7355]">Commission de la plateforme</p>
        </div>

        {{-- Net créateur --}}
        <div class="stat-card-premium border-2 border-[#22C55E]/30" style="--stat-color-1: #22C55E; --stat-color-2: #16A34A;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Net créateur</p>
                    <p class="text-4xl font-bold text-[#22C55E]" style="font-family: 'Playfair Display', serif;">{{ number_format($netRevenue, 0, ',', ' ') }}</p>
                    <p class="text-sm text-[#8B7355] mt-1">FCFA</p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-[#22C55E] to-[#16A34A] flex items-center justify-center shadow-lg">
                    <i class="fas fa-wallet text-white text-3xl"></i>
                </div>
            </div>
            <p class="text-xs text-[#8B7355]">Montant après commission</p>
        </div>
    </div>

    {{-- Statistiques globales --}}
    <div class="premium-card mb-8">
        <h3 class="text-xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-chart-bar text-[#ED5F1E] mr-2"></i>
            Statistiques globales (toutes périodes)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-gradient-to-br from-[#F8F6F3] to-white rounded-xl border border-[#E5DDD3]">
                <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">CA Brut total</p>
                <p class="text-3xl font-bold text-[#ED5F1E]" style="font-family: 'Playfair Display', serif;">{{ number_format($allTimeStats['gross'], 0, ',', ' ') }} F</p>
            </div>
            <div class="p-4 bg-gradient-to-br from-[#F8F6F3] to-white rounded-xl border border-[#E5DDD3]">
                <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Commissions totales</p>
                <p class="text-3xl font-bold text-[#F59E0B]" style="font-family: 'Playfair Display', serif;">{{ number_format($allTimeStats['commission'], 0, ',', ' ') }} F</p>
            </div>
            <div class="p-4 bg-gradient-to-br from-[#F8F6F3] to-white rounded-xl border border-[#E5DDD3]">
                <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Net total</p>
                <p class="text-3xl font-bold text-[#22C55E]" style="font-family: 'Playfair Display', serif;">{{ number_format($allTimeStats['net'], 0, ',', ' ') }} F</p>
            </div>
        </div>
    </div>

    {{-- Historique des commandes payées --}}
    <div class="premium-card overflow-hidden">
        <div class="pb-6 mb-6 border-b-2 border-[#E5DDD3]">
            <h3 class="text-xl font-bold text-[#2C1810]" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-history text-[#ED5F1E] mr-2"></i>
                Dernières commandes payées
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>CA Brut</th>
                        <th>Commission</th>
                        <th>Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPaidOrders as $order)
                    <tr>
                        <td>
                            <p class="font-bold text-[#2C1810]">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </td>
                        <td>
                            <p class="text-[#8B7355] font-medium">{{ $order->created_at->format('d/m/Y') }}</p>
                        </td>
                        <td>
                            <p class="font-bold text-[#ED5F1E] text-lg">{{ number_format($order->creator_gross, 0, ',', ' ') }} F</p>
                        </td>
                        <td>
                            <p class="font-semibold text-[#F59E0B]">{{ number_format($order->creator_commission, 0, ',', ' ') }} F</p>
                        </td>
                        <td>
                            <p class="font-bold text-[#22C55E] text-lg">{{ number_format($order->creator_net, 0, ',', ' ') }} F</p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-wallet text-5xl text-[#8B7355]"></i>
                                <p class="text-xl font-bold text-[#2C1810]">Aucune commande payée pour le moment</p>
                                <p class="text-[#8B7355]">Vos commandes payées apparaîtront ici</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
