@extends('layouts.admin-master')

@section('title', 'Portfolio CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🎨 Portfolio CMS</h1>
            <p class="text-muted mb-0">Gérez les projets du portfolio</p>
        </div>
        <a href="{{ route('cms.admin.portfolio.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau projet
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
                            <th>Projet</th>
                            <th>Catégorie</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>
                                    @if($item->featured_image)
                                        <img src="{{ asset('storage/' . $item->featured_image) }}" 
                                             alt="{{ $item->title }}" 
                                             class="rounded me-2" 
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    @endif
                                    <strong>{{ $item->title }}</strong>
                                </td>
                                <td>
                                    @if($item->category)
                                        <span class="badge bg-info">{{ $item->category }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $item->client ?? '-' }}</td>
                                <td>
                                    <small>{{ $item->project_date ? $item->project_date->format('d/m/Y') : '-' }}</small>
                                </td>
                                <td>
                                    @if($item->status === 'published')
                                        <span class="badge bg-success">Publié</span>
                                    @else
                                        <span class="badge bg-warning">Brouillon</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.portfolio.edit', $item) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.portfolio.destroy', $item) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce projet ?');">
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
                                    <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                    Aucun projet dans le portfolio. <a href="{{ route('cms.admin.portfolio.create') }}">Créer le premier</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="mt-3">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

