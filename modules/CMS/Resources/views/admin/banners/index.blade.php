@extends('layouts.admin-master')

@section('title', 'Bannières CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🖼️ Bannières CMS</h1>
            <p class="text-muted mb-0">Gérez les bannières du site</p>
        </div>
        <a href="{{ route('cms.admin.banners.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle bannière
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
                            <th>Bannière</th>
                            <th>Titre</th>
                            <th>Position</th>
                            <th>Ordre</th>
                            <th>Dates</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banners as $banner)
                            <tr>
                                <td>{{ $banner->id }}</td>
                                <td>
                                    @if($banner->image)
                                        <img src="{{ asset('storage/' . $banner->image) }}" 
                                             alt="{{ $banner->title }}" 
                                             class="rounded" 
                                             style="width: 100px; height: 50px; object-fit: cover;">
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $banner->title ?? '-' }}</strong>
                                    @if($banner->subtitle)
                                        <br><small class="text-muted">{{ $banner->subtitle }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $banner->position }}</span>
                                </td>
                                <td>{{ $banner->order ?? 0 }}</td>
                                <td>
                                    <small class="text-muted">
                                        @if($banner->start_date || $banner->end_date)
                                            {{ $banner->start_date ? $banner->start_date->format('d/m/Y') : '∞' }} - 
                                            {{ $banner->end_date ? $banner->end_date->format('d/m/Y') : '∞' }}
                                        @else
                                            Toujours active
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @if($banner->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.banners.edit', $banner) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.banners.destroy', $banner) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette bannière ?');">
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
                                    <i class="fas fa-image fa-3x mb-3 d-block"></i>
                                    Aucune bannière créée. <a href="{{ route('cms.admin.banners.create') }}">Créer la première</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($banners->hasPages())
                <div class="mt-3">
                    {{ $banners->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

