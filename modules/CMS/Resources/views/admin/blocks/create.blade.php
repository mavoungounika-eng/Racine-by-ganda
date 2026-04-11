@extends('layouts.admin-master')

@section('title', 'Créer un bloc CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">➕ Créer un bloc CMS</h1>
            <p class="text-muted mb-0">Créez un nouveau bloc de contenu réutilisable</p>
        </div>
        <a href="{{ route('cms.admin.blocks.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('cms.admin.blocks.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="identifier" class="form-label">Identifiant <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('identifier') is-invalid @enderror" 
                                   id="identifier" name="identifier" value="{{ old('identifier') }}" required
                                   placeholder="Ex: hero_home, footer_about">
                            <small class="form-text text-muted">Identifiant unique utilisé dans le code (ex: hero_home)</small>
                            @error('identifier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Contenu</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content" rows="10">{{ old('content') }}</textarea>
                            <small class="form-text text-muted">HTML, JSON ou texte selon le type</small>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">Paramètres</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        @foreach($types as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}" {{ old('type') == $typeKey ? 'selected' : '' }}>
                                                {{ $typeLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="zone" class="form-label">Zone <span class="text-danger">*</span></label>
                                    <select class="form-select @error('zone') is-invalid @enderror" 
                                            id="zone" name="zone" required>
                                        @foreach($zones as $zoneKey => $zoneLabel)
                                            <option value="{{ $zoneKey }}" {{ old('zone') == $zoneKey ? 'selected' : '' }}>
                                                {{ $zoneLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('zone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="page_slug" class="form-label">Page spécifique</label>
                                    <input type="text" class="form-control @error('page_slug') is-invalid @enderror" 
                                           id="page_slug" name="page_slug" value="{{ old('page_slug') }}"
                                           placeholder="Laissez vide pour toutes les pages">
                                    <small class="form-text text-muted">Slug de la page (optionnel)</small>
                                    @error('page_slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="order" class="form-label">Ordre</label>
                                    <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                           id="order" name="order" value="{{ old('order', 0) }}" min="0">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Actif
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le bloc
                    </button>
                    <a href="{{ route('cms.admin.blocks.index') }}" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

