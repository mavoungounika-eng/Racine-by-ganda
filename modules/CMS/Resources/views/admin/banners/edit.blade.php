@extends('layouts.admin-master')

@section('title', 'Éditer une bannière')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">✏️ Éditer la bannière</h1>
            <p class="text-muted mb-0">{{ $banner->title ?? 'Bannière #' . $banner->id }}</p>
        </div>
        <a href="{{ route('cms.admin.banners') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('cms.admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $banner->title) }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Sous-titre</label>
                            <input type="text" class="form-control @error('subtitle') is-invalid @enderror" 
                                   id="subtitle" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}">
                            @error('subtitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="link_url" class="form-label">URL du lien</label>
                            <input type="url" class="form-control @error('link_url') is-invalid @enderror" 
                                   id="link_url" name="link_url" value="{{ old('link_url', $banner->link_url) }}">
                            @error('link_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="link_text" class="form-label">Texte du lien</label>
                            <input type="text" class="form-control @error('link_text') is-invalid @enderror" 
                                   id="link_text" name="link_text" value="{{ old('link_text', $banner->link_text) }}">
                            @error('link_text')
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
                                    <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('position') is-invalid @enderror" 
                                           id="position" name="position" value="{{ old('position', $banner->position) }}" required>
                                    @error('position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="order" class="form-label">Ordre</label>
                                    <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                           id="order" name="order" value="{{ old('order', $banner->order) }}" min="0">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Date de début</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" 
                                           value="{{ old('start_date', $banner->start_date ? $banner->start_date->format('Y-m-d') : '') }}">
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Date de fin</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" 
                                           value="{{ old('end_date', $banner->end_date ? $banner->end_date->format('Y-m-d') : '') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">Image (Desktop)</label>
                                    @if($banner->image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $banner->image) }}" 
                                                 alt="Image actuelle" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                           id="image" name="image" accept="image/*">
                                    @error('image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="image_mobile" class="form-label">Image (Mobile)</label>
                                    @if($banner->image_mobile)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $banner->image_mobile) }}" 
                                                 alt="Image mobile actuelle" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('image_mobile') is-invalid @enderror" 
                                           id="image_mobile" name="image_mobile" accept="image/*">
                                    @error('image_mobile')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="{{ route('cms.admin.banners') }}" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

