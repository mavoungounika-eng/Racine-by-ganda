@extends('layouts.admin-master')

@section('title', 'Éditer un projet portfolio')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">✏️ Éditer le projet</h1>
            <p class="text-muted mb-0">{{ $portfolio->title }}</p>
        </div>
        <a href="{{ route('cms.admin.portfolio') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('cms.admin.portfolio.update', $portfolio) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $portfolio->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" name="slug" value="{{ old('slug', $portfolio->slug) }}">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $portfolio->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Contenu</label>
                            <x-tinymce-editor 
                                name="content" 
                                :value="old('content', $portfolio->content)" 
                                :height="400"
                                placeholder="Écrivez le contenu du projet..."
                            />
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">Informations du projet</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Catégorie</label>
                                    <input type="text" class="form-control @error('category') is-invalid @enderror" 
                                           id="category" name="category" value="{{ old('category', $portfolio->category) }}">
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="client" class="form-label">Client</label>
                                    <input type="text" class="form-control @error('client') is-invalid @enderror" 
                                           id="client" name="client" value="{{ old('client', $portfolio->client) }}">
                                    @error('client')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="project_date" class="form-label">Date du projet</label>
                                    <input type="date" class="form-control @error('project_date') is-invalid @enderror" 
                                           id="project_date" name="project_date" 
                                           value="{{ old('project_date', $portfolio->project_date ? $portfolio->project_date->format('Y-m-d') : '') }}">
                                    @error('project_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="draft" {{ old('status', $portfolio->status) == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                        <option value="published" {{ old('status', $portfolio->status) == 'published' ? 'selected' : '' }}>Publié</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="featured_image" class="form-label">Image mise en avant</label>
                                    @if($portfolio->featured_image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $portfolio->featured_image) }}" 
                                                 alt="Image actuelle" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('featured_image') is-invalid @enderror" 
                                           id="featured_image" name="featured_image" accept="image/*">
                                    @error('featured_image')
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
                    <a href="{{ route('cms.admin.portfolio') }}" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

