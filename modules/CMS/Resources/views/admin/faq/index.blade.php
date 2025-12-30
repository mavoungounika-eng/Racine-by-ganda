@extends('layouts.admin-master')

@section('title', 'FAQ CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">❓ FAQ CMS</h1>
            <p class="text-muted mb-0">Gérez les questions fréquentes</p>
        </div>
        <div>
            <a href="{{ route('cms.admin.faq.categories') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-folder"></i> Catégories
            </a>
            <a href="{{ route('cms.admin.faq.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle FAQ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('cms.admin.faq.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="category" class="form-label">Catégorie</label>
                    <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Question ou réponse">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Question</th>
                            <th>Catégorie</th>
                            <th>Ordre</th>
                            <th>Statut</th>
                            <th>Vues</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faqs as $faq)
                            <tr>
                                <td>{{ $faq->id }}</td>
                                <td>
                                    <strong>{{ Str::limit($faq->question, 60) }}</strong>
                                    @if($faq->is_featured)
                                        <span class="badge bg-warning ms-2">Mise en avant</span>
                                    @endif
                                </td>
                                <td>
                                    @if($faq->category)
                                        <span class="badge bg-info">{{ $faq->category->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $faq->order ?? 0 }}</td>
                                <td>
                                    @if($faq->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $faq->views ?? 0 }} vues</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.faq.edit', $faq) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.faq.destroy', $faq) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette FAQ ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-question-circle fa-3x mb-3 d-block"></i>
                                    Aucune FAQ créée. <a href="{{ route('cms.admin.faq.create') }}">Créer la première</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($faqs->hasPages())
                <div class="mt-3">
                    {{ $faqs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

