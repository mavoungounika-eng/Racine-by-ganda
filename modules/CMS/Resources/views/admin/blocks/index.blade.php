@extends('layouts.admin-master')

@section('title', 'Blocs CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🧩 Blocs CMS</h1>
            <p class="text-muted mb-0">Gérez les blocs de contenu réutilisables</p>
        </div>
        <a href="{{ route('cms.admin.blocks.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau bloc
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('cms.admin.blocks.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="zone" class="form-label">Zone</label>
                    <select class="form-select" id="zone" name="zone" onchange="this.form.submit()">
                        <option value="">Toutes les zones</option>
                        @foreach($zones as $zoneKey => $zoneLabel)
                            <option value="{{ $zoneKey }}" {{ request('zone') == $zoneKey ? 'selected' : '' }}>
                                {{ $zoneLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Nom ou identifiant">
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
                            <th>Nom</th>
                            <th>Identifiant</th>
                            <th>Type</th>
                            <th>Zone</th>
                            <th>Page</th>
                            <th>Ordre</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blocks as $block)
                            <tr>
                                <td>{{ $block->id }}</td>
                                <td><strong>{{ $block->name }}</strong></td>
                                <td><code>{{ $block->identifier }}</code></td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($block->type) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $zones[$block->zone] ?? $block->zone }}</span>
                                </td>
                                <td>
                                    @if($block->page_slug)
                                        <small class="text-muted">{{ $block->page_slug }}</small>
                                    @else
                                        <span class="text-muted">Toutes</span>
                                    @endif
                                </td>
                                <td>{{ $block->order ?? 0 }}</td>
                                <td>
                                    @if($block->is_active)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.blocks.edit', $block) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.blocks.destroy', $block) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bloc ?');">
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
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fas fa-puzzle-piece fa-3x mb-3 d-block"></i>
                                    Aucun bloc créé. <a href="{{ route('cms.admin.blocks.create') }}">Créer le premier</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($blocks->hasPages())
                <div class="mt-3">
                    {{ $blocks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

