@extends('layouts.creator')

@section('title', 'Mes Produits - RACINE BY GANDA')
@section('page-title', 'Mes Produits')


@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Stats rapides --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="creator-stat-card-premium" style="--stat-color-1: #D4A574; --stat-color-2: #8B5A2B;">
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
        
        <div class="creator-stat-card-premium" style="--stat-color-1: #22C55E; --stat-color-2: #16A34A;">
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
        
        <div class="creator-stat-card-premium" style="--stat-color-1: #94A3B8; --stat-color-2: #64748B;">
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
    <div class="creator-card mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4 flex-wrap">
                <a href="{{ route('creator.products.create') }}" class="creator-btn">
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
                       class="creator-input">
                
                <select name="status" class="creator-input">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                </select>
                
                <button type="submit" class="creator-btn" style="padding: 0.75rem 1.25rem;">
                    <i class="fas fa-search"></i>
                </button>
                
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('creator.products.index') }}" class="creator-btn" style="background: linear-gradient(135deg, #64748B 0%, #475569 100%); padding: 0.75rem 1.25rem;">
                        <i class="fas fa-redo"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Liste des produits --}}
    <div class="creator-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="creator-table">
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
                                <span class="creator-badge" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <i class="fas fa-check"></i>
                                    {{ $product->stock ?? 'N/A' }}
                                </span>
                            @elseif($product->stock > 0)
                                <span class="creator-badge" style="background: rgba(234, 179, 8, 0.1); color: #F59E0B; border: 1px solid rgba(234, 179, 8, 0.2);">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    {{ $product->stock }}
                                </span>
                            @else
                                <span class="creator-badge" style="background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2);">
                                    <i class="fas fa-times"></i>
                                    0
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="creator-badge" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <i class="fas fa-check-circle"></i>
                                    Actif
                                </span>
                            @else
                                <span class="creator-badge" style="background: rgba(148, 163, 184, 0.1); color: #64748B; border: 1px solid rgba(148, 163, 184, 0.2);">
                                    <i class="fas fa-pause-circle"></i>
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('creator.products.edit', $product) }}" 
                                   class="creator-action-btn"
                                   style="background: rgba(59, 130, 246, 0.1); color: #3B82F6; border-color: rgba(59, 130, 246, 0.2);"
                                   title="Modifier">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                @if(!$product->is_active)
                                    <form action="{{ route('creator.products.publish', $product) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="creator-action-btn"
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
                                            class="creator-action-btn"
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
                            <div class="creator-empty-state">
                                <i class="fas fa-box-open text-5xl text-[#8B7355] mb-4"></i>
                                <p class="text-xl font-bold text-[#2C1810] mb-2">Aucun produit trouvé</p>
                                <p class="text-[#8B7355] mb-4">Commencez par créer votre premier produit</p>
                                <a href="{{ route('creator.products.create') }}" class="creator-btn">
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
