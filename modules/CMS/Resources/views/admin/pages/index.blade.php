@extends('layouts.admin-master')

@section('title', 'Pages CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📄 Pages CMS</h1>
            <p class="text-muted mb-0">Gérez les pages de votre site</p>
        </div>
        <a href="{{ route('cms.admin.pages.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle page
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Slug</th>
                            <th>Template</th>
                            <th>Auteur</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            <tr>
                                <td>{{ $page->id }}</td>
                                <td>
                                    <strong>{{ $page->title }}</strong>
                                    @if($page->excerpt)
                                        <br><small class="text-muted">{{ Str::limit($page->excerpt, 50) }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $page->slug }}</code></td>
                                <td>
                                    <span class="badge bg-secondary">{{ $page->template ?? 'default' }}</span>
                                </td>
                                <td>
                                    @if($page->author)
                                        {{ $page->author->name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($page->status === 'published')
                                        <span class="badge bg-success">Publié</span>
                                    @elseif($page->status === 'draft')
                                        <span class="badge bg-warning">Brouillon</span>
                                    @else
                                        <span class="badge bg-secondary">Archivé</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $page->published_at ? $page->published_at->format('d/m/Y') : '-' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.pages.edit', $page) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.pages.destroy', $page) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette page ?');">
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
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-file-alt fa-3x mb-3 d-block"></i>
                                    Aucune page créée. <a href="{{ route('cms.admin.pages.create') }}">Créer la première</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pages->hasPages())
                <div class="mt-3">
                    {{ $pages->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

