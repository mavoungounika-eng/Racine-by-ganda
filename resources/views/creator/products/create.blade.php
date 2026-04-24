@extends('layouts.creator')

@section('title', 'Nouveau Produit - RACINE BY GANDA')
@section('page-title', 'Nouveau Produit')

@push('styles')
<style nonce="{{ csp_nonce() }}">
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
    }

    .form-group {
        margin-bottom: 1.75rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #160D0C;
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
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        font-size: 0.95rem;
        color: #160D0C;
        background: white;
        transition: all 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #D4A574;
        box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.1);
    }

    .form-control::placeholder {
        color: #8B7355;
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
        color: #160D0C;
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
        color: #160D0C;
    }

    .error-message {
        color: #EF4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .form-help {
        color: #8B7355;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container py-4" style="max-width: 960px;">
    <div class="premium-card">
        <div class="mb-4 pb-3 border-bottom" style="border-color: #E5DDD3 !important;">
            <h2 class="h2 fw-bold" style="font-family: 'Libre Baskerville', serif; color: #160D0C;">
                <i class="fas fa-plus-circle me-2" style="color: #ED5F1E;"></i>
                Créer un nouveau produit
            </h2>
            <p class="mt-2 mb-0" style="color: #8B7355;">Remplissez les informations ci-dessous pour ajouter un nouveau produit à votre boutique</p>
        </div>

        <form method="POST" action="{{ route('creator.products.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="title" class="form-label">
                    Titre du produit <span class="required">*</span>
                </label>
                <input type="text"
                       id="title"
                       name="title"
                       value="{{ old('title') }}"
                       required
                       placeholder="Ex: Robe traditionnelle en pagne"
                       class="form-control">
                @error('title')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="description" class="form-label">
                    Description
                </label>
                <textarea id="description"
                          name="description"
                          rows="5"
                          placeholder="Décrivez votre produit en détail..."
                          class="form-control">{{ old('description') }}</textarea>
                @error('description')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="price" class="form-label">
                            Prix (FCFA) <span class="required">*</span>
                        </label>
                        <input type="number"
                               id="price"
                               name="price"
                               value="{{ old('price') }}"
                               step="0.01"
                               min="0"
                               required
                               placeholder="0"
                               class="form-control">
                        @error('price')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="stock" class="form-label">
                            Stock <span class="required">*</span>
                        </label>
                        <input type="number"
                               id="stock"
                               name="stock"
                               value="{{ old('stock', 0) }}"
                               min="0"
                               required
                               placeholder="0"
                               class="form-control">
                        @error('stock')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="category_id" class="form-label">
                    Catégorie <span class="required">*</span>
                </label>
                <select id="category_id"
                        name="category_id"
                        required
                        class="form-control">
                    <option value="">-- Sélectionner une catégorie --</option>

                    @foreach($categories as $parent)
                        @if($parent->children->count() > 0)
                            <optgroup label="{{ $parent->name }}">
                                @foreach($parent->children as $child)
                                    <option value="{{ $child->id }}" {{ old('category_id') == $child->id ? 'selected' : '' }}>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @else
                            <option value="{{ $parent->id }}" {{ old('category_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <p class="form-help">Choisissez la catégorie la plus précise pour votre produit</p>
                @error('category_id')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="main_image" class="form-label">
                    Image principale
                </label>
                <input type="file"
                       id="main_image"
                       name="main_image"
                       accept="image/*"
                       class="form-control">
                <p class="form-help">Format: JPG, PNG (max 4MB). L'image sera redimensionnée automatiquement.</p>
                @error('main_image')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background: linear-gradient(90deg, #F8F6F3 0%, #FFFFFF 100%); border: 1px solid #E5DDD3;">
                    <input type="checkbox"
                           id="is_active"
                           name="is_active"
                           value="1"
                           {{ old('is_active') ? 'checked' : '' }}
                           class="form-check-input m-0">
                    <label for="is_active" class="mb-0 fw-medium" style="color: #160D0C; cursor: pointer;">
                        Publier immédiatement (activer le produit)
                    </label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-3 pt-4 border-top" style="border-color: #E5DDD3 !important;">
                <a href="{{ route('creator.products.index') }}" class="premium-btn-secondary">
                    <i class="fas fa-times"></i>
                    Annuler
                </a>
                <button type="submit" class="premium-btn">
                    <i class="fas fa-save"></i>
                    Enregistrer le produit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
