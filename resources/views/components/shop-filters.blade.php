{{-- Filtres Boutique RACINE BY GANDA --}}
<div class="shop-filters bg-white rounded-2xl shadow-lg p-6 sticky top-24">
    <form method="GET" action="{{ route('frontend.shop') }}" id="shopFiltersForm">
        
        {{-- Filtre Genre --}}
        <div class="filter-section mb-6">
            <h3 class="text-lg font-bold text-[#2C1810] mb-4 flex items-center gap-2">
                <i class="fas fa-venus-mars text-[#ED5F1E]"></i>
                Genre
            </h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="gender" 
                           value="" 
                           {{ !request('gender') ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">Tous</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="gender" 
                           value="femme" 
                           {{ request('gender') === 'femme' ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">👗 Femme</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="gender" 
                           value="homme" 
                           {{ request('gender') === 'homme' ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">👔 Homme</span>
                </label>
            </div>
        </div>

        <hr class="border-[#E5DDD3] my-6">

        {{-- Filtre Type de Vendeur --}}
        <div class="filter-section mb-6">
            <h3 class="text-lg font-bold text-[#2C1810] mb-4 flex items-center gap-2">
                <i class="fas fa-store text-[#ED5F1E]"></i>
                Type de Vendeur
            </h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="product_type" 
                           value="" 
                           {{ !request('product_type') ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">Tous</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="product_type" 
                           value="brand" 
                           {{ request('product_type') === 'brand' ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">👑 RACINE BY GANDA</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="product_type" 
                           value="marketplace" 
                           {{ request('product_type') === 'marketplace' ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">🎨 Créateurs Partenaires</span>
                </label>
            </div>
        </div>

        <hr class="border-[#E5DDD3] my-6">

        {{-- Filtre Catégories --}}
        <div class="filter-section mb-6">
            <h3 class="text-lg font-bold text-[#2C1810] mb-4 flex items-center gap-2">
                <i class="fas fa-tags text-[#ED5F1E]"></i>
                Catégories
            </h3>
            <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                @foreach($categories as $parent)
                    <div class="category-group">
                        <div class="font-semibold text-[#2C1810] mb-2 text-sm uppercase tracking-wide">
                            {{ $parent->name }}
                        </div>
                        <div class="space-y-2 ml-3">
                            @foreach($parent->children as $child)
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox" 
                                           name="category[]" 
                                           value="{{ $child->id }}"
                                           {{ in_array($child->id, (array) request('category', [])) ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] rounded focus:ring-[#ED5F1E]"
                                           onchange="this.form.submit()">
                                    <span class="text-sm text-[#8B7355] group-hover:text-[#ED5F1E] transition flex-1">
                                        {{ $child->name }}
                                    </span>
                                    @if($child->products_count > 0)
                                        <span class="text-xs text-[#8B7355] bg-[#F8F6F3] px-2 py-0.5 rounded-full">
                                            {{ $child->products_count }}
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <hr class="border-[#E5DDD3] my-6">

        {{-- Filtre Prix --}}
        <div class="filter-section mb-6">
            <h3 class="text-lg font-bold text-[#2C1810] mb-4 flex items-center gap-2">
                <i class="fas fa-coins text-[#ED5F1E]"></i>
                Prix (FCFA)
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-[#8B7355] mb-1 block">Minimum</label>
                    <input type="number" 
                           name="price_min" 
                           value="{{ request('price_min') }}"
                           placeholder="0"
                           class="w-full px-3 py-2 border-2 border-[#E5DDD3] rounded-lg focus:border-[#ED5F1E] focus:ring-2 focus:ring-[#ED5F1E]/20">
                </div>
                <div>
                    <label class="text-sm text-[#8B7355] mb-1 block">Maximum</label>
                    <input type="number" 
                           name="price_max" 
                           value="{{ request('price_max') }}"
                           placeholder="1000000"
                           class="w-full px-3 py-2 border-2 border-[#E5DDD3] rounded-lg focus:border-[#ED5F1E] focus:ring-2 focus:ring-[#ED5F1E]/20">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white py-2 rounded-lg font-semibold hover:shadow-lg transition">
                    Appliquer
                </button>
            </div>
        </div>

        <hr class="border-[#E5DDD3] my-6">

        {{-- Filtre Stock --}}
        <div class="filter-section mb-6">
            <h3 class="text-lg font-bold text-[#2C1810] mb-4 flex items-center gap-2">
                <i class="fas fa-box text-[#ED5F1E]"></i>
                Disponibilité
            </h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="stock_filter" 
                           value="" 
                           {{ !request('stock_filter') ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">Tous</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="stock_filter" 
                           value="in_stock" 
                           {{ request('stock_filter') === 'in_stock' ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">✅ En stock</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" 
                           name="stock_filter" 
                           value="low_stock" 
                           {{ request('stock_filter') === 'low_stock' ? 'checked' : '' }}
                           class="w-4 h-4 text-[#ED5F1E] border-[#E5DDD3] focus:ring-[#ED5F1E]"
                           onchange="this.form.submit()">
                    <span class="text-[#2C1810] group-hover:text-[#ED5F1E] transition">⚠️ Stock limité</span>
                </label>
            </div>
        </div>

        {{-- Bouton Réinitialiser --}}
        @if(request()->hasAny(['gender', 'product_type', 'category', 'price_min', 'price_max', 'stock_filter', 'search']))
            <a href="{{ route('frontend.shop') }}" 
               class="block w-full text-center py-2 px-4 border-2 border-[#E5DDD3] text-[#2C1810] rounded-lg hover:bg-[#F8F6F3] transition font-semibold">
                <i class="fas fa-redo mr-2"></i>
                Réinitialiser les filtres
            </a>
        @endif
    </form>
</div>

<style>
    .shop-filters::-webkit-scrollbar {
        width: 6px;
    }
    .shop-filters::-webkit-scrollbar-track {
        background: #F8F6F3;
        border-radius: 3px;
    }
    .shop-filters::-webkit-scrollbar-thumb {
        background: #E5DDD3;
        border-radius: 3px;
    }
    .shop-filters::-webkit-scrollbar-thumb:hover {
        background: #D4A574;
    }
</style>
