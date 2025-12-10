@extends('layouts.admin-master')

@section('title', 'Éditer une FAQ')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">✏️ Éditer la FAQ</h1>
            <p class="text-muted mb-0">{{ Str::limit($faq->question, 60) }}</p>
        </div>
        <a href="{{ route('cms.admin.faq.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('cms.admin.faq.update', $faq) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('question') is-invalid @enderror" 
                                   id="question" name="question" value="{{ old('question', $faq->question) }}" required
                                   maxlength="500">
                            @error('question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="answer" class="form-label">Réponse <span class="text-danger">*</span></label>
                            <x-tinymce-editor 
                                name="answer" 
                                :value="old('answer', $faq->answer)" 
                                :height="300"
                                placeholder="Écrivez la réponse à la question..."
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
                                    <label for="category_id" class="form-label">Catégorie</label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" name="category_id">
                                        <option value="">Aucune catégorie</option>
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" {{ old('category_id', $faq->category_id) == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="order" class="form-label">Ordre</label>
                                    <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                           id="order" name="order" value="{{ old('order', $faq->order) }}" min="0">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_featured" 
                                               name="is_featured" value="1" {{ old('is_featured', $faq->is_featured) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured">
                                            Mise en avant
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', $faq->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <strong>Statistiques :</strong><br>
                                        Vues : {{ $faq->views ?? 0 }}<br>
                                        Utile : {{ $faq->helpful_yes ?? 0 }} / 
                                        Pas utile : {{ $faq->helpful_no ?? 0 }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="{{ route('cms.admin.faq.index') }}" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

