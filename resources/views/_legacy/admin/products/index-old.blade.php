@extends('layouts.admin')

@section('title', 'Produits')
@section('page-title', 'Gestion des Produits')

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
        <div>
            <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-box text-racine-orange mr-2"></i>
                Gestion des Produits
            </h2>
            <p class="text-slate-400">{{ $products->total() }} produits au total</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
           class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Nouveau Produit
        </a>
    </div>

    <div class="premium-card">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Rechercher..." 
                   class="premium-input">
            
            <select name="category" class="premium-select">
                <option value="">Toutes catégories</option>
                @foreach(\App\Models\Category::all() as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-racine-orange to-racine-yellow text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-racine-orange/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-search"></i>
                Filtrer
            </button>
            @if(request()->hasAny(['search', 'category']))
                <a href="{{ route('admin.products.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>SKU</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="h-16 w-16 rounded-xl overflow-hidden bg-[#160D0C] border border-slate-700">
                                @if($product->main_image)
                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-slate-600"></i>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <p class="font-semibold text-white">{{ $product->title }}</p>
                            @if($product->description)
                                <p class="text-xs text-slate-400 mt-1 line-clamp-1">{{ Str::limit($product->description, 50) }}</p>
                            @endif
                        </td>
                        <td>
                            @if($product->sku)
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-racine-orange">{{ $product->sku }}</span>
                                    <button onclick="copyToClipboard('{{ $product->sku }}')" class="text-racine-orange hover:text-racine-gold text-xs" title="Copier SKU">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                @if($product->barcode)
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-mono text-slate-400">{{ $product->barcode }}</span>
                                        <button onclick="copyToClipboard('{{ $product->barcode }}')" class="text-slate-400 hover:text-slate-300 text-xs" title="Copier code-barres">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @endif
                            @else
                                <span class="text-xs text-slate-500">Non généré</span>
                            @endif
                        </td>
                        <td class="text-slate-300">{{ $product->category?->name ?? 'N/A' }}</td>
                        <td>
                            <p class="font-bold text-racine-orange">{{ number_format($product->price, 0, ',', ' ') }} F</p>
                        </td>
                        <td>
                            @if($product->stock > 10)
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">{{ $product->stock }}</span>
                            @elseif($product->stock > 0)
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-semibold">{{ $product->stock }}</span>
                            @else
                                <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold">0</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.products.edit', $product) }}"
                                   class="h-9 w-9 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 flex items-center justify-center transition hover:scale-110"
                                   title="Modifier">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
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
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-box-open text-5xl text-slate-600"></i>
                                <p class="text-slate-400 text-lg">Aucun produit trouvé</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-slate-700">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Afficher une notification de succès
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        notification.innerHTML = '<i class="fas fa-check mr-2"></i>Copié : ' + text;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }).catch(function(err) {
        console.error('Erreur lors de la copie:', err);
        alert('Erreur lors de la copie');
    });
}
</script>
@endpush
