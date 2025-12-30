@extends('layouts.creator')

@section('title', 'Détail Commande #' . str_pad($order->id, 6, '0', STR_PAD_LEFT) . ' - RACINE BY GANDA')
@section('page-title', 'Détail Commande #' . str_pad($order->id, 6, '0', STR_PAD_LEFT))

@push('styles')
<style>
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .premium-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
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
    
    .premium-table tbody tr:hover {
        background: linear-gradient(90deg, rgba(212, 165, 116, 0.05) 0%, transparent 100%);
    }
    
    .badge-premium {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 999px;
        font-size: 0.875rem;
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
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .premium-btn-secondary {
        background: white;
        color: #2C1810;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    
    .premium-btn-secondary:hover {
        background: #F8F6F3;
        border-color: #D4A574;
        color: #2C1810;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Informations commande --}}
    <div class="premium-card">
        <div class="flex items-center justify-between mb-6 pb-6 border-b-2 border-[#E5DDD3]">
            <div>
                <h3 class="text-2xl font-bold text-[#2C1810] mb-2" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-shopping-bag text-[#ED5F1E] mr-2"></i>
                    Informations de la commande
                </h3>
                <p class="text-[#8B7355]">
                    <i class="fas fa-calendar mr-1"></i>
                    Date: {{ $order->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>
            <div>
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
                <span class="badge-premium" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }}; border: 2px solid {{ $status['border'] }};">
                    <i class="fas {{ $status['icon'] }}"></i>
                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Informations client --}}
            <div class="p-4 bg-gradient-to-br from-[#F8F6F3] to-white rounded-xl border border-[#E5DDD3]">
                <h4 class="text-sm font-bold text-[#8B7355] mb-4 uppercase tracking-wide">
                    <i class="fas fa-user mr-2"></i>
                    Client
                </h4>
                <div class="space-y-2 text-sm">
                    <p class="text-[#2C1810]"><span class="text-[#8B7355] font-medium">Nom:</span> <span class="font-semibold">{{ $order->customer_name ?? $order->user?->name ?? 'N/A' }}</span></p>
                    <p class="text-[#2C1810]"><span class="text-[#8B7355] font-medium">Email:</span> {{ $order->customer_email ?? $order->user?->email ?? 'N/A' }}</p>
                    @if($order->customer_phone)
                        <p class="text-[#2C1810]"><span class="text-[#8B7355] font-medium">Téléphone:</span> {{ $order->customer_phone }}</p>
                    @endif
                </div>
            </div>

            {{-- Adresse de livraison --}}
            <div class="p-4 bg-gradient-to-br from-[#F8F6F3] to-white rounded-xl border border-[#E5DDD3]">
                <h4 class="text-sm font-bold text-[#8B7355] mb-4 uppercase tracking-wide">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    Adresse de livraison
                </h4>
                <div class="text-sm text-[#2C1810] leading-relaxed">
                    {!! nl2br(e($order->customer_address ?? 'Non renseignée')) !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Produits de la commande --}}
    <div class="premium-card">
        <h3 class="text-xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-box text-[#ED5F1E] mr-2"></i>
            Vos produits dans cette commande
        </h3>
        
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix unitaire</th>
                        <th>Quantité</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creatorItems as $item)
                    <tr>
                        <td>
                            <div class="flex items-center gap-4">
                                @if($item->product && $item->product->main_image)
                                    <img src="{{ asset('storage/' . $item->product->main_image) }}" 
                                         alt="{{ $item->product->title }}" 
                                         class="h-16 w-16 rounded-xl object-cover border-2 border-[#E5DDD3]">
                                @else
                                    <div class="h-16 w-16 rounded-xl bg-gradient-to-br from-[#F8F6F3] to-[#E5DDD3] flex items-center justify-center border-2 border-[#E5DDD3]">
                                        <i class="fas fa-image text-[#8B7355] text-xl"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-[#2C1810]">{{ $item->product->title ?? 'Produit supprimé' }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="text-[#8B7355] font-medium">{{ number_format($item->price, 0, ',', ' ') }} F</p>
                        </td>
                        <td>
                            <span class="px-3 py-1 bg-gradient-to-r from-[#F8F6F3] to-white rounded-lg border border-[#E5DDD3] font-semibold text-[#2C1810]">
                                {{ $item->quantity }}
                            </span>
                        </td>
                        <td class="text-right">
                            <p class="font-bold text-[#ED5F1E] text-lg">{{ number_format($item->price * $item->quantity, 0, ',', ' ') }} F</p>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-[#D4A574]">
                        <td colspan="3" class="py-4 px-4 text-right font-bold text-[#2C1810] text-lg">
                            Total (vos produits):
                        </td>
                        <td class="py-4 px-4 text-right">
                            <p class="text-2xl font-bold text-[#ED5F1E]" style="font-family: 'Playfair Display', serif;">{{ number_format($creatorTotal, 0, ',', ' ') }} F</p>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Mise à jour du statut --}}
    <div class="premium-card">
        <h3 class="text-xl font-bold text-[#2C1810] mb-6" style="font-family: 'Libre Baskerville', serif;">
            <i class="fas fa-sync-alt text-[#ED5F1E] mr-2"></i>
            Mettre à jour le statut
        </h3>
        
        <form method="POST" action="{{ route('creator.orders.updateStatus', $order) }}" class="flex items-center gap-4">
            @csrf
            @method('PATCH')
            
            <select name="status" class="premium-select flex-1">
                @foreach($availableStatuses as $value => $label)
                    <option value="{{ $value }}" {{ $order->status === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <button type="submit" class="premium-btn">
                <i class="fas fa-save"></i>
                Mettre à jour
            </button>
        </form>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end">
        <a href="{{ route('creator.orders.index') }}" class="premium-btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Retour à la liste
        </a>
    </div>
</div>
@endsection
