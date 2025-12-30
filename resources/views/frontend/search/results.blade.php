@extends('layouts.frontend')

@section('title', 'Recherche - RACINE BY GANDA')

@section('content')
<div class="hero-wrap hero-bread" style="background-image: url('{{ asset('racine/images/bg_6.jpg') }}');">
    <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center">
                <h1 class="mb-0 bread">Recherche</h1>
            </div>
        </div>
    </div>
</div>

<section class="ftco-section">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filtres -->
            <div class="col-md-3">
                <div class="sidebar">
                    <h3>Filtres</h3>
                    
                    <form method="GET" action="{{ route('frontend.search') }}" id="search-form">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        
                        <!-- Recherche -->
                        <div class="form-group mb-3">
                            <label>Recherche</label>
                            <input type="text" name="q" class="form-control" 
                                   value="{{ $filters['q'] ?? '' }}" 
                                   placeholder="Rechercher...">
                        </div>

                        <!-- Catégorie -->
                        <div class="form-group mb-3">
                            <label>Catégorie</label>
                            <select name="category" class="form-control">
                                <option value="">Toutes</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ ($filters['category'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Prix -->
                        <div class="form-group mb-3">
                            <label>Prix (FCFA)</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" name="price_min" class="form-control" 
                                           placeholder="Min" value="{{ $filters['price_min'] ?? '' }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="price_max" class="form-control" 
                                           placeholder="Max" value="{{ $filters['price_max'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- Stock -->
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="in_stock" value="1" 
                                       class="form-check-input" id="in_stock"
                                       {{ ($filters['in_stock'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="in_stock">
                                    En stock uniquement
                                </label>
                            </div>
                        </div>

                        <!-- Tri -->
                        <div class="form-group mb-3">
                            <label>Trier par</label>
                            <select name="sort" class="form-control">
                                <option value="created_at" {{ ($filters['sort'] ?? '') == 'created_at' ? 'selected' : '' }}>Plus récent</option>
                                <option value="price_asc" {{ ($filters['sort'] ?? '') == 'price_asc' ? 'selected' : '' }}>Prix croissant</option>
                                <option value="price_desc" {{ ($filters['sort'] ?? '') == 'price_desc' ? 'selected' : '' }}>Prix décroissant</option>
                                <option value="title" {{ ($filters['sort'] ?? '') == 'title' ? 'selected' : '' }}>Nom A-Z</option>
                                <option value="popularity" {{ ($filters['sort'] ?? '') == 'popularity' ? 'selected' : '' }}>Popularité</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" class="btn-racine-primary">Appliquer</button>
                        <a href="{{ route('frontend.search') }}" class="btn btn-secondary btn-block">Réinitialiser</a>
                    </form>
                </div>
            </div>

            <!-- Résultats -->
            <div class="col-md-9">
                @if($products->count() > 0)
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0">
                        <strong>{{ $products->total() }}</strong> produit(s) trouvé(s)
                        @if($filters['q'])
                        pour "<strong>{{ $filters['q'] }}</strong>"
                        @endif
                    </p>
                </div>

                <div class="row">
                    @foreach($products as $product)
                    <div class="col-md-4 mb-4">
                        <div class="product-item">
                            <a href="{{ route('frontend.product', $product->id) }}" class="img-prod">
                                <img class="img-fluid" 
                                     src="{{ $product->main_image ? asset('storage/' . $product->main_image) : asset('racine/images/product-1.jpg') }}" 
                                     alt="{{ $product->title }}">
                                @if($product->stock <= 0)
                                <span class="status">Rupture de stock</span>
                                @elseif($product->hasLowStock(10))
                                <span class="status">Stock faible</span>
                                @endif
                            </a>
                            <div class="text py-3 pb-4 px-3 text-center">
                                <h3><a href="{{ route('frontend.product', $product->id) }}">{{ $product->title }}</a></h3>
                                <div class="d-flex">
                                    <div class="pricing">
                                        <p class="price">
                                            <span class="price-sale">{{ number_format($product->price, 0, ',', ' ') }} FCFA</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="bottom-area d-flex px-3">
                                    <div class="m-auto d-flex">
                                        <a href="{{ route('frontend.product', $product->id) }}" 
                                           class="buy-now d-flex justify-content-center align-items-center text-center">
                                            <span><i class="icon-eye"></i></span>
                                        </a>
                                        @if($product->stock > 0)
                                        <form action="{{ route('cart.add') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="buy-now d-flex justify-content-center align-items-center text-center">
                                                <span><i class="icon-shopping-cart"></i></span>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="row mt-5">
                    <div class="col text-center">
                        {{ $products->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="icon-search" style="font-size: 4rem; color: #ccc;"></i>
                    <h3 class="mt-3">Aucun résultat</h3>
                    <p class="text-muted">Essayez de modifier vos critères de recherche.</p>
                    <a href="{{ route('frontend.shop') }}" class="btn btn-primary mt-3" class="btn-racine-primary">
                        Voir tous les produits
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Autocomplete pour la recherche
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="q"]');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                const term = this.value;
                
                if (term.length >= 2) {
                    timeout = setTimeout(() => {
                        fetch('{{ route("frontend.search.suggest") }}?q=' + encodeURIComponent(term))
                            .then(response => response.json())
                            .then(data => {
                                // Afficher les suggestions (à implémenter avec un dropdown)
                                console.log('Suggestions:', data);
                            });
                    }, 300);
                }
            });
        }
    });
</script>
@endpush

