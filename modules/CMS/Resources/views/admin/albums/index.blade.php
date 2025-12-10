@extends('layouts.admin-master')

@section('title', 'Albums CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📸 Albums CMS</h1>
            <p class="text-muted mb-0">Gérez les albums photo</p>
        </div>
        <a href="{{ route('cms.admin.albums.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel album
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
                            <th>Album</th>
                            <th>Catégorie</th>
                            <th>Photos</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($albums as $album)
                            <tr>
                                <td>{{ $album->id }}</td>
                                <td>
                                    @if($album->cover_image)
                                        <img src="{{ asset('storage/' . $album->cover_image) }}" 
                                             alt="{{ $album->title }}" 
                                             class="rounded me-2" 
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    @endif
                                    <strong>{{ $album->title }}</strong>
                                    @if($album->is_featured)
                                        <span class="badge bg-warning">Mise en avant</span>
                                    @endif
                                </td>
                                <td>
                                    @if($album->category)
                                        <span class="badge bg-info">{{ $album->category }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $album->photo_count ?? 0 }} photos</span>
                                </td>
                                <td>
                                    <small>{{ $album->album_date ? $album->album_date->format('d/m/Y') : '-' }}</small>
                                </td>
                                <td>
                                    @if($album->status === 'published')
                                        <span class="badge bg-success">Publié</span>
                                    @else
                                        <span class="badge bg-warning">Brouillon</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.albums.edit', $album) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.albums.destroy', $album) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet album ?');">
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
                                    <i class="fas fa-photo-video fa-3x mb-3 d-block"></i>
                                    Aucun album créé. <a href="{{ route('cms.admin.albums.create') }}">Créer le premier</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($albums->hasPages())
                <div class="mt-3">
                    {{ $albums->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

