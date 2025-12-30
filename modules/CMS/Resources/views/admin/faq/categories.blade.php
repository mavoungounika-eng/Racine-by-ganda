@extends('layouts.admin-master')

@section('title', 'Catégories FAQ')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📁 Catégories FAQ</h1>
            <p class="text-muted mb-0">Gérez les catégories de questions fréquentes</p>
        </div>
        <a href="{{ route('cms.admin.faq.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour FAQ
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Créer une catégorie</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('cms.admin.faq.category.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" name="slug" value="{{ old('slug') }}" required>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icône</label>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror" 
                                   id="icon" name="icon" value="{{ old('icon') }}" 
                                   placeholder="Ex: fas fa-question">
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Slug</th>
                                    <th>FAQ</th>
                                    <th>Ordre</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td>
                                            @if($category->icon)
                                                <i class="{{ $category->icon }} me-2"></i>
                                            @endif
                                            <strong>{{ $category->name }}</strong>
                                        </td>
                                        <td><code>{{ $category->slug }}</code></td>
                                        <td>
                                            <span class="badge bg-info">{{ $category->faqs_count ?? 0 }}</span>
                                        </td>
                                        <td>{{ $category->order ?? 0 }}</td>
                                        <td>
                                            @if($category->is_active ?? true)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#editCategory{{ $category->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('cms.admin.faq.category.destroy', $category) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Êtes-vous sûr ? Les FAQ de cette catégorie seront déplacées sans catégorie.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            Aucune catégorie créée. Créez-en une à gauche.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($categories as $category)
        <!-- Modal Edit Category -->
        <div class="modal fade" id="editCategory{{ $category->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('cms.admin.faq.category.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Éditer la catégorie</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name_{{ $category->id }}" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="name_{{ $category->id }}" 
                                       name="name" value="{{ $category->name }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="slug_{{ $category->id }}" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug_{{ $category->id }}" 
                                       name="slug" value="{{ $category->slug }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="icon_{{ $category->id }}" class="form-label">Icône</label>
                                <input type="text" class="form-control" id="icon_{{ $category->id }}" 
                                       name="icon" value="{{ $category->icon }}">
                            </div>
                            <div class="mb-3">
                                <label for="description_{{ $category->id }}" class="form-label">Description</label>
                                <textarea class="form-control" id="description_{{ $category->id }}" 
                                          name="description" rows="3">{{ $category->description }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="order_{{ $category->id }}" class="form-label">Ordre</label>
                                <input type="number" class="form-control" id="order_{{ $category->id }}" 
                                       name="order" value="{{ $category->order }}" min="0">
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active_{{ $category->id }}" 
                                           name="is_active" value="1" {{ ($category->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active_{{ $category->id }}">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

