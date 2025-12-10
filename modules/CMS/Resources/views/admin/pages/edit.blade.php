@extends('layouts.admin-master')

@section('title', 'Éditer une page CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">✏️ Éditer la page CMS</h1>
            <p class="text-muted mb-0">{{ $page->title }}</p>
        </div>
        <a href="{{ route('cms.admin.pages') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('cms.admin.pages.update', $page) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $page->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" name="slug" value="{{ old('slug', $page->slug) }}">
                            <small class="form-text text-muted">Identifiant unique de la page</small>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Extrait</label>
                            <textarea class="form-control @error('excerpt') is-invalid @enderror" 
                                      id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $page->excerpt) }}</textarea>
                            @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Contenu</label>
                            <x-tinymce-editor 
                                name="content" 
                                :value="old('content', $page->content)" 
                                :height="500"
                                placeholder="Écrivez le contenu de votre page..."
                            />
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">Paramètres</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="draft" {{ old('status', $page->status) == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                        <option value="published" {{ old('status', $page->status) == 'published' ? 'selected' : '' }}>Publié</option>
                                        <option value="archived" {{ old('status', $page->status) == 'archived' ? 'selected' : '' }}>Archivé</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="template" class="form-label">Template</label>
                                    <select class="form-select @error('template') is-invalid @enderror" 
                                            id="template" name="template">
                                        <option value="default" {{ old('template', $page->template) == 'default' ? 'selected' : '' }}>Par défaut</option>
                                        <option value="full-width" {{ old('template', $page->template) == 'full-width' ? 'selected' : '' }}>Pleine largeur</option>
                                        <option value="sidebar" {{ old('template', $page->template) == 'sidebar' ? 'selected' : '' }}>Avec sidebar</option>
                                    </select>
                                    @error('template')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="featured_image" class="form-label">Image mise en avant</label>
                                    @if($page->featured_image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $page->featured_image) }}" 
                                                 alt="Image actuelle" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('featured_image') is-invalid @enderror" 
                                           id="featured_image" name="featured_image" accept="image/*">
                                    @error('featured_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="order" class="form-label">Ordre</label>
                                    <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                           id="order" name="order" value="{{ old('order', $page->order) }}" min="0">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">SEO</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="meta_title" class="form-label">Titre SEO</label>
                                    <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                                           id="meta_title" name="meta_title" 
                                           value="{{ old('meta_title', $page->meta['title'] ?? '') }}"
                                           maxlength="70">
                                    @error('meta_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Description SEO</label>
                                    <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                              id="meta_description" name="meta_description" rows="3"
                                              maxlength="160">{{ old('meta_description', $page->meta['description'] ?? '') }}</textarea>
                                    @error('meta_description')
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
                    <a href="{{ route('cms.admin.pages') }}" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

