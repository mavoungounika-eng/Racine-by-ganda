@extends('layouts.admin-master')

@section('title', 'Gestion des Créateurs')
@section('page-title', 'Gestion des Créateurs')
@section('page-subtitle', 'Gérer les créateurs partenaires et leurs documents')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-users text-racine-orange me-2"></i>
            Gestion des Créateurs
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Gérer les créateurs partenaires et leurs documents
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.creators.index', ['status' => 'pending']) }}" class="btn btn-outline-warning">
            <i class="fas fa-clock me-2"></i>
            En attente ({{ \App\Models\CreatorProfile::where('status', 'pending')->count() }})
        </a>
        <a href="{{ route('admin.creators.export.csv') }}" class="btn btn-outline-primary">
            <i class="fas fa-download me-2"></i>
            Exporter CSV
        </a>
        <a href="{{ route('admin.creators.reports.validation') }}" class="btn btn-outline-info">
            <i class="fas fa-chart-bar me-2"></i>
            Rapports
        </a>
    </div>
</div>

{{-- Statistiques --}}
<div class="row g-4 mb-4">
    @php
        $totalCreators = \App\Models\CreatorProfile::count();
        $verifiedCreators = \App\Models\CreatorProfile::where('is_verified', true)->count();
        $pendingCreators = \App\Models\CreatorProfile::where('status', 'pending')->count();
        $activeCreators = \App\Models\CreatorProfile::where('status', 'active')->where('is_active', true)->count();
    @endphp
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Total Créateurs',
            'value' => $totalCreators,
            'icon' => 'fas fa-users',
            'color' => 'primary'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Vérifiés',
            'value' => $verifiedCreators,
            'icon' => 'fas fa-check-circle',
            'color' => 'success'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'En attente',
            'value' => $pendingCreators,
            'icon' => 'fas fa-clock',
            'color' => 'warning'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Actifs',
            'value' => $activeCreators,
            'icon' => 'fas fa-check',
            'color' => 'info'
        ])
    </div>
</div>

{{-- Barre de filtres --}}
<div class="card card-racine mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.creators.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label small text-muted mb-1">
                    <i class="fas fa-search me-1"></i> Recherche
                </label>
                <input type="text" 
                       name="search" 
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Nom, email, marque..." 
                       class="form-control form-control-lg">
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label small text-muted mb-1">
                    <i class="fas fa-filter me-1"></i> Statut
                </label>
                <select name="status" id="status" class="form-select form-select-lg">
                    <option value="">Tous les statuts</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendus</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="is_verified" class="form-label small text-muted mb-1">
                    <i class="fas fa-check-circle me-1"></i> Vérification
                </label>
                <select name="is_verified" id="is_verified" class="form-select form-select-lg">
                    <option value="">Tous</option>
                    <option value="1" {{ request('is_verified') === '1' ? 'selected' : '' }}>Vérifiés</option>
                    <option value="0" {{ request('is_verified') === '0' ? 'selected' : '' }}>Non vérifiés</option>
                </select>
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-racine-orange btn-lg w-100">
                    <i class="fas fa-filter me-2"></i>
                    Filtrer
                </button>
            </div>

            @if(request()->hasAny(['search', 'status', 'is_verified']))
                <div class="col-md-auto">
                    <a href="{{ route('admin.creators.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                        <i class="fas fa-redo me-2"></i>
                        Réinitialiser
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Liste des créateurs --}}
<div class="card card-racine">
    <div class="card-body p-0">
        @if($creators->count())
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-user me-2"></i>Créateur
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-envelope me-2"></i>Email
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-box me-2"></i>Produits
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-file-alt me-2"></i>Documents
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-toggle-on me-2"></i>Statut
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-calendar me-2"></i>Inscription
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creators as $creator)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="d-flex align-items-center gap-3">
                                @if($creator->logo_path)
                                    <img src="{{ asset('storage/' . $creator->logo_path) }}" 
                                         class="rounded-circle" 
                                         style="width: 48px; height: 48px; object-fit: cover; border: 2px solid var(--racine-beige);">
                                @else
                                    <div class="rounded-circle bg-racine-beige d-flex align-items-center justify-content-center" 
                                         style="width: 48px; height: 48px;">
                                        <i class="fas fa-user text-racine-orange"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-racine-black">{{ $creator->brand_name }}</div>
                                    <div class="small text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        {{ $creator->user->name }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="text-racine-black">
                                <i class="fas fa-envelope me-1 text-muted"></i>
                                {{ $creator->user->email }}
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-box me-1"></i>
                                {{ $creator->products_count ?? 0 }}
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @php
                                $documentsCount = $creator->documents_count ?? 0;
                                $verifiedDocsCount = $creator->verified_documents_count ?? 0;
                            @endphp
                            @if($documentsCount > 0)
                                <a href="{{ route('admin.creators.show', $creator) }}" 
                                   class="badge bg-info text-white text-decoration-none">
                                    <i class="fas fa-file-alt me-1"></i>
                                    {{ $documentsCount }} document{{ $documentsCount > 1 ? 's' : '' }}
                                    @if($verifiedDocsCount > 0)
                                        <span class="badge bg-success ms-1">{{ $verifiedDocsCount }} vérifié{{ $verifiedDocsCount > 1 ? 's' : '' }}</span>
                                    @endif
                                </a>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    Aucun document
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($creator->is_verified)
                                <span class="badge bg-success rounded-pill mb-1 d-inline-block">
                                    <i class="fas fa-check-circle me-1"></i>Vérifié
                                </span>
                            @else
                                <span class="badge bg-warning rounded-pill mb-1 d-inline-block">
                                    <i class="fas fa-clock me-1"></i>En attente
                                </span>
                            @endif
                            <br>
                            @if($creator->is_active && $creator->status === 'active')
                                <span class="badge bg-primary rounded-pill">
                                    <i class="fas fa-check me-1"></i>Actif
                                </span>
                            @elseif($creator->status === 'suspended')
                                <span class="badge bg-danger rounded-pill">
                                    <i class="fas fa-ban me-1"></i>Suspendu
                                </span>
                            @else
                                <span class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-pause me-1"></i>Inactif
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="text-racine-black small">
                                <i class="fas fa-calendar me-1 text-muted"></i>
                                {{ $creator->created_at->format('d/m/Y') }}
                            </div>
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.creators.show', $creator) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Voir les détails">
                                    <i class="fas fa-eye"></i>
                                    <span class="d-none d-md-inline ms-1">Voir</span>
                                </a>
                                <form action="{{ route('admin.creators.verify', $creator->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="btn btn-sm {{ $creator->is_verified ? 'btn-warning' : 'btn-success' }}"
                                            title="{{ $creator->is_verified ? 'Retirer la vérification' : 'Vérifier le créateur' }}"
                                            onclick="return confirm('Êtes-vous sûr de vouloir {{ $creator->is_verified ? 'retirer la vérification' : 'vérifier' }} ce créateur ?')">
                                        <i class="fas fa-{{ $creator->is_verified ? 'times' : 'check' }}"></i>
                                        <span class="d-none d-md-inline ms-1">{{ $creator->is_verified ? 'Retirer' : 'Vérifier' }}</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($creators->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $creators->firstItem() ?? 0 }} à {{ $creators->lastItem() ?? 0 }} sur {{ $creators->total() }} résultats
                </div>
                <div>
                    {{ $creators->links() }}
                </div>
            </div>
        </div>
        @endif
        @else
        <div class="text-center py-5">
            <div class="py-4">
                <i class="fas fa-users fa-3x text-muted mb-3 opacity-50"></i>
                <p class="text-muted mb-2">Aucun créateur trouvé</p>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

