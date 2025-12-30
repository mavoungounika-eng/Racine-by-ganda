@extends('layouts.admin-master')

@section('title', 'Créer une Catégorie')
@section('page-title', 'Créer une Catégorie')
@section('page-subtitle', 'Ajouter une nouvelle catégorie de produits')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-4">
                <h3 class="mb-0 fw-bold">
                    <i class="fas fa-folder-plus text-racine-orange me-2"></i>
                    Créer une nouvelle catégorie
                </h3>
                <p class="text-muted mb-0 mt-2">
                    Remplissez le formulaire ci-dessous pour créer une nouvelle catégorie
                </p>
            </div>
            
            <div class="card-body">
                <form action="{{ route('admin.categories.store') }}" method="POST">
                    @csrf

                    {{-- Section Informations générales --}}
                    <div class="mb-5">
                        <h5 class="fw-bold mb-4 text-racine-black">
                            <i class="fas fa-info-circle text-racine-orange me-2"></i>
                            Informations générales
                        </h5>
                        <div class="row g-4">
                            @include('partials.admin.form-group', [
                                'label' => 'Nom de la catégorie',
                                'name' => 'name',
                                'type' => 'text',
                                'required' => true,
                                'col' => 6,
                                'value' => old('name'),
                                'placeholder' => 'Nom de la catégorie'
                            ])

                            @include('partials.admin.form-group', [
                                'label' => 'Slug (Optionnel)',
                                'name' => 'slug',
                                'type' => 'text',
                                'required' => false,
                                'col' => 6,
                                'value' => old('slug'),
                                'placeholder' => 'Généré automatiquement si vide',
                                'help' => 'Laissez vide pour génération automatique'
                            ])

                            <div class="col-md-6 mb-4">
                                <label for="parent_id" class="form-label fw-semibold mb-2">
                                    Catégorie Parente
                                </label>
                                <select name="parent_id" id="parent_id"
                                        class="form-select @error('parent_id') is-invalid @enderror">
                                    <option value="">Aucune (Racine)</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('parent_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="form-text text-muted small mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Sélectionnez une catégorie parente pour créer une sous-catégorie
                                </div>
                            </div>

                            @include('partials.admin.form-group', [
                                'label' => 'Statut',
                                'name' => 'is_active',
                                'type' => 'checkbox',
                                'required' => false,
                                'col' => 6,
                                'checked' => old('is_active', true),
                                'checkLabel' => 'Catégorie active (visible sur le site)'
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
                                'label' => 'Description de la catégorie',
                                'name' => 'description',
                                'type' => 'textarea',
                                'required' => false,
                                'col' => 12,
                                'rows' => 4,
                                'value' => old('description'),
                                'placeholder' => 'Décrivez cette catégorie...',
                                'help' => 'Description optionnelle qui apparaîtra sur le site'
                            ])
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-racine-orange">
                            <i class="fas fa-save me-2"></i>
                            Créer la catégorie
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

