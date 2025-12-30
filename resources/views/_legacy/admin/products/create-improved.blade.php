@extends('layouts.admin')

@section('title', 'Créer un Produit')
@section('page-title', 'Créer un Produit')
@section('page-subtitle', 'Ajouter un nouveau produit à la plateforme')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-4">
                <h3 class="mb-0 fw-bold">
                    <i class="fas fa-plus-circle text-racine-orange me-2"></i>
                    Créer un nouveau produit
                </h3>
                <p class="text-muted mb-0 mt-2">
                    Remplissez le formulaire ci-dessous pour créer un nouveau produit
                </p>
            </div>
            
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Section Informations générales --}}
                    <div class="mb-5">
                        <h5 class="fw-bold mb-4 text-racine-black">
                            <i class="fas fa-info-circle text-racine-orange me-2"></i>
                            Informations générales
                        </h5>
                        <div class="row g-4">
                            @include('partials.admin.form-group', [
                                'label' => 'Titre du produit',
                                'name' => 'title',
                                'type' => 'text',
                                'required' => true,
                                'col' => 12,
                                'value' => old('title'),
                                'placeholder' => 'Nom du produit'
                            ])

                            @include('partials.admin.form-group', [
                                'label' => 'Slug (Optionnel)',
                                'name' => 'slug',
                                'type' => 'text',
                                'required' => false,
                                'col' => 12,
                                'value' => old('slug'),
                                'placeholder' => 'Généré automatiquement si vide',
                                'help' => 'Laissez vide pour génération automatique'
                            ])

                            @include('partials.admin.form-group', [
                                'label' => 'Catégorie',
                                'name' => 'category_id',
                                'type' => 'select',
                                'required' => true,
                                'col' => 6
                            ])
                                <option value="">Sélectionner une catégorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endinclude

                            @include('partials.admin.form-group', [
                                'label' => 'Statut',
                                'name' => 'is_active',
                                'type' => 'checkbox',
                                'required' => false,
                                'col' => 6,
                                'checked' => old('is_active', true),
                                'checkLabel' => 'Produit actif (visible sur le site)'
                            ])
                        </div>
                    </div>

                    {{-- Section Prix et Stock --}}
                    <div class="mb-5">
                        <h5 class="fw-bold mb-4 text-racine-black">
                            <i class="fas fa-dollar-sign text-racine-orange me-2"></i>
                            Prix et Stock
                        </h5>
                        <div class="row g-4">
                            @include('partials.admin.form-group', [
                                'label' => 'Prix (FCFA)',
                                'name' => 'price',
                                'type' => 'number',
                                'required' => true,
                                'col' => 6,
                                'value' => old('price'),
                                'placeholder' => '0',
                                'step' => '0.01',
                                'min' => '0'
                            ])

                            @include('partials.admin.form-group', [
                                'label' => 'Stock disponible',
                                'name' => 'stock',
                                'type' => 'number',
                                'required' => true,
                                'col' => 6,
                                'value' => old('stock', 0),
                                'placeholder' => '0',
                                'min' => '0',
                                'help' => 'Nombre d\'unités en stock'
                            ])
                        </div>
                    </div>

                    {{-- Section Image --}}
                    <div class="mb-5">
                        <h5 class="fw-bold mb-4 text-racine-black">
                            <i class="fas fa-image text-racine-orange me-2"></i>
                            Image principale
                        </h5>
                        <div class="row g-4">
                            @include('partials.admin.form-group', [
                                'label' => 'Image du produit',
                                'name' => 'main_image',
                                'type' => 'file',
                                'required' => false,
                                'col' => 12,
                                'accept' => 'image/*',
                                'help' => 'JPG, PNG ou WEBP. Taille maximale : 2MB'
                            ])
                        </div>
                    </div>

                    {{-- Section Description --}}
                    <div class="mb-5">
                        <h5 class="fw-bold mb-4 text-racine-black">
                            <i class="fas fa-align-left text-racine-orange me-2"></i>
                            Description
                        </h5>
                        <div class="row g-4">
                            @include('partials.admin.form-group', [
                                'label' => 'Description du produit',
                                'name' => 'description',
                                'type' => 'textarea',
                                'required' => false,
                                'col' => 12,
                                'rows' => 6,
                                'value' => old('description'),
                                'placeholder' => 'Décrivez votre produit en détail...'
                            ])
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-racine-orange">
                            <i class="fas fa-save me-2"></i>
                            Créer le produit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

