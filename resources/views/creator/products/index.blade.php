@extends('layouts.creator')

@section('title', 'Mes Produits - RACINE BY GANDA')
@section('page-title', 'Mes Produits')

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
    
    .premium-input {
        background: white;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #2C1810;
        transition: all 0.3s;
    }
    
    .premium-input:focus {
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
    
    .empty-state-premium {
        text-align: center;
        padding: 4rem 2rem;
        background: linear-gradient(135deg, #F8F6F3 0%, white 100%);
        border-radius: 20px;
        border: 2px dashed #D4A574;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Stats rapides --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card-premium" style="--stat-color-1: #D4A574; --stat-color-2: #8B5A2B;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Total Produits</p>
                    <p class="text-3xl font-bold text-[#2C1810]" style="font-family: 'Playfair Display', serif;">{{ $stats['total'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#D4A574] to-[#8B5A2B] flex items-center justify-center shadow-lg">
                    <i class="fas fa-box text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-premium" style="--stat-color-1: #22C55E; --stat-color-2: #16A34A;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Actifs</p>
                    <p class="text-3xl font-bold text-[#22C55E]" style="font-family: 'Playfair Display', serif;">{{ $stats['active'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#22C55E] to-[#16A34A] flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-premium" style="--stat-color-1: #94A3B8; --stat-color-2: #64748B;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-[#8B7355] uppercase tracking-wide mb-2 font-semibold">Inactifs</p>
                    <p class="text-3xl font-bold text-[#64748B]" style="font-family: 'Playfair Display', serif;">{{ $stats['inactive'] }}</p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-[#94A3B8] to-[#64748B] flex items-center justify-center shadow-lg">
                    <i class="fas fa-pause-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre d'actions --}}
    <div class="premium-card mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4 flex-wrap">
                <a href="{{ route('creator.products.create') }}" class="premium-btn">
                    <i class="fas fa-plus"></i>
                    Nouveau Produit
                </a>
            </div>
            
            {{-- Filtres --}}
            <form method="GET" class="flex items-center gap-3 flex-wrap">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Rechercher..." 
                       class="premium-input">
                
                <select name="status" class="premium-input">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                </select>
                
                <button type="submit" class="premium-btn" style="padding: 0.75rem 1.25rem;">
                    <i class="fas fa-search"></i>
                </button>
                
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('creator.products.index') }}" class="premium-btn" style="background: linear-gradient(135deg, #64748B 0%, #475569 100%); padding: 0.75rem 1.25rem;">
                        <i class="fas fa-redo"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Liste des produits --}}
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="h-20 w-20 rounded-xl overflow-hidden bg-gradient-to-br from-[#F8F6F3] to-[#E5DDD3] shadow-md">
                                @if($product->main_image)
                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-[#8B7355] text-2xl"></i>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <p class="font-bold text-[#2C1810] mb-1">{{ $product->title }}</p>
                            @if($product->description)
                                <p class="text-sm text-[#8B7355] line-clamp-1">{{ Str::limit($product->description, 50) }}</p>
                            @endif
                        </td>
                        <td>
                            <span class="text-[#8B7355] font-medium">{{ $product->category?->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <p class="font-bold text-[#ED5F1E] text-lg">{{ number_format($product->price, 0, ',', ' ') }} F</p>
                        </td>
                        <td>
                            @if($product->stock > 10)
                                <span class="badge-premium" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <i class="fas fa-check"></i>
                                    {{ $product->stock ?? 'N/A' }}
                                </span>
                            @elseif($product->stock > 0)
                                <span class="badge-premium" style="background: rgba(234, 179, 8, 0.1); color: #F59E0B; border: 1px solid rgba(234, 179, 8, 0.2);">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    {{ $product->stock }}
                                </span>
                            @else
                                <span class="badge-premium" style="background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2);">
                                    <i class="fas fa-times"></i>
                                    0
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge-premium" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <i class="fas fa-check-circle"></i>
                                    Actif
                                </span>
                            @else
                                <span class="badge-premium" style="background: rgba(148, 163, 184, 0.1); color: #64748B; border: 1px solid rgba(148, 163, 184, 0.2);">
                                    <i class="fas fa-pause-circle"></i>
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('creator.products.edit', $product) }}" 
                                   class="action-btn-premium"
                                   style="background: rgba(59, 130, 246, 0.1); color: #3B82F6; border-color: rgba(59, 130, 246, 0.2);"
                                   title="Modifier">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                @if(!$product->is_active)
                                    <form action="{{ route('creator.products.publish', $product) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="action-btn-premium"
                                                style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border-color: rgba(34, 197, 94, 0.2);"
                                                title="Publier">
                                            <i class="fas fa-check text-sm"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('creator.products.destroy', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir désactiver ce produit ?')" 
                                            class="action-btn-premium"
                                            style="background: rgba(239, 68, 68, 0.1); color: #EF4444; border-color: rgba(239, 68, 68, 0.2);"
                                            title="Désactiver">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12">
                            <div class="empty-state-premium">
                                <i class="fas fa-box-open text-5xl text-[#8B7355] mb-4"></i>
                                <p class="text-xl font-bold text-[#2C1810] mb-2">Aucun produit trouvé</p>
                                <p class="text-[#8B7355] mb-4">Commencez par créer votre premier produit</p>
                                <a href="{{ route('creator.products.create') }}" class="premium-btn">
                                    <i class="fas fa-plus"></i>
                                    Créer votre premier produit
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-[#E5DDD3] bg-gradient-to-r from-[#F8F6F3] to-white">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
