@extends('layouts.admin')

@section('title', 'Modifier un Produit')
@section('page-title', 'Modifier le Produit')

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .form-group {
        margin-bottom: 1.75rem;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #e2e8f0;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }
    
    .form-label .required {
        color: #EF4444;
        margin-left: 0.25rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.875rem 1.25rem;
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        font-size: 0.95rem;
        color: #e2e8f0;
        background: rgba(22, 13, 12, 0.6);
        transition: all 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
    
    .form-control::placeholder {
        color: #64748B;
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
        background: rgba(51, 65, 85, 0.6);
        color: #e2e8f0;
        border: 2px solid rgba(212, 165, 116, 0.2);
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
        background: rgba(51, 65, 85, 0.8);
        border-color: rgba(212, 165, 116, 0.4);
        color: #e2e8f0;
    }
    
    .error-message {
        color: #EF4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="premium-card">
        <div class="mb-8 pb-6 border-b-2 border-slate-700">
            <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-edit text-racine-orange mr-2"></i>
                Modifier le produit
            </h2>
            <p class="text-slate-400">Modifiez les informations du produit ci-dessous</p>
        </div>

        {{-- Affichage SKU et Code-barres --}}
        @if($product->sku || $product->barcode)
        <div class="mb-6 p-4 bg-gradient-to-r from-racine-orange/20 to-racine-gold/20 border border-racine-orange/30 rounded-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wide mb-1 block">SKU</label>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-barcode text-racine-orange"></i>
                        <span class="text-white font-mono font-semibold">{{ $product->sku ?? 'Non généré' }}</span>
                        @if($product->sku)
                        <button onclick="copyToClipboard('{{ $product->sku }}')" class="ml-2 text-racine-orange hover:text-racine-gold" title="Copier">
                            <i class="fas fa-copy"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wide mb-1 block">Code-barres</label>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-qrcode text-racine-orange"></i>
                        <span class="text-white font-mono font-semibold">{{ $product->barcode ?? 'Non généré' }}</span>
                        @if($product->barcode)
                        <button onclick="copyToClipboard('{{ $product->barcode }}')" class="ml-2 text-racine-orange hover:text-racine-gold" title="Copier">
                            <i class="fas fa-copy"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="col-span-2 sm:col-span-1 form-group">
                    <label for="title" class="form-label">Titre <span class="required">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $product->title) }}" required
                           placeholder="Nom du produit"
                           class="form-control @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2 sm:col-span-1 form-group">
                    <label for="slug" class="form-label">Slug <span class="required">*</span></label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $product->slug) }}" required
                           placeholder="slug-du-produit"
                           class="form-control @error('slug') border-red-500 @enderror">
                    @error('slug')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2 sm:col-span-1 form-group">
                    <label for="category_id" class="form-label">Catégorie <span class="required">*</span></label>
                    <select name="category_id" id="category_id" required
                            class="form-control @error('category_id') border-red-500 @enderror">
                        <option value="">Sélectionner une catégorie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2 sm:col-span-1 form-group">
                    <label class="form-label">Statut</label>
                    <div class="flex items-center gap-3 p-4 bg-[#160D0C]/40 rounded-xl border border-slate-700/50">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 text-racine-orange border-slate-700 rounded focus:ring-racine-orange bg-[#160D0C]">
                        <label for="is_active" class="text-slate-300 font-medium cursor-pointer">Actif</label>
                    </div>
                </div>

                <div class="col-span-2 sm:col-span-1 form-group">
                    <label for="price" class="form-label">Prix (FCFA) <span class="required">*</span></label>
                    <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required
                           placeholder="0"
                           class="form-control @error('price') border-red-500 @enderror">
                    @error('price')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2 sm:col-span-1 form-group">
                    <label for="stock" class="form-label">Stock <span class="required">*</span></label>
                    <input type="number" name="stock" id="stock" value="{{ old('stock', $product->stock) }}" min="0" required
                           placeholder="0"
                           class="form-control @error('stock') border-red-500 @enderror">
                    @error('stock')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2 form-group">
                    <label for="main_image" class="form-label">Image principale</label>
                    @if($product->main_image)
                        <div class="mb-4">
                            <p class="text-sm text-slate-400 mb-2">Image actuelle:</p>
                            <img src="{{ asset('storage/' . $product->main_image) }}" alt="Image actuelle"
                                 class="h-32 w-32 object-cover rounded-xl border border-slate-700">
                        </div>
                    @endif
                    <input type="file" name="main_image" id="main_image" accept="image/*"
                           class="form-control @error('main_image') border-red-500 @enderror">
                    <p class="text-xs text-slate-400 mt-2">Laisser vide pour conserver l'image actuelle. JPG, PNG, WEBP jusqu'à 2MB.</p>
                    @error('main_image')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2 form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" rows="4"
                              placeholder="Description du produit..."
                              class="form-control @error('description') border-red-500 @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t border-slate-700">
                <button type="submit" class="premium-btn">
                    <i class="fas fa-save"></i>
                    Enregistrer les modifications
                </button>
                <a href="{{ route('admin.products.index') }}" class="premium-btn-secondary">
                    <i class="fas fa-times"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>

    {{-- Galerie d'Images --}}
    @include('admin.products.partials.gallery')
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
