@extends('layouts.admin-master')

@section('title', 'CMS - Gestionnaire de Contenu')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📝 Gestionnaire de Contenu</h1>
            <p class="text-muted mb-0">Gérez le contenu de votre site RACINE BY GANDA</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-file-alt text-primary fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['pages'] }}</h4>
                            <span class="text-muted small">Pages</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cms.admin.pages') }}" class="text-primary small">
                        Gérer <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-calendar-alt text-success fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['events'] }}</h4>
                            <span class="text-muted small">Événements</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cms.admin.events') }}" class="text-success small">
                        Gérer <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-images text-warning fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['portfolio'] }}</h4>
                            <span class="text-muted small">Portfolio</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cms.admin.portfolio') }}" class="text-warning small">
                        Gérer <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-photo-video text-info fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-0">{{ $stats['albums'] }}</h4>
                            <span class="text-muted small">Albums</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('cms.admin.albums') }}" class="text-info small">
                        Gérer <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-bolt text-warning me-2"></i>Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('cms.admin.pages.create') }}" class="btn btn-outline-primary btn-sm text-start">
                            <i class="fas fa-plus me-2"></i> Nouvelle page
                        </a>
                        <a href="{{ route('cms.admin.events.create') }}" class="btn btn-outline-success btn-sm text-start">
                            <i class="fas fa-calendar-plus me-2"></i> Nouvel événement
                        </a>
                        <a href="{{ route('cms.admin.portfolio.create') }}" class="btn btn-outline-warning btn-sm text-start">
                            <i class="fas fa-folder-plus me-2"></i> Nouveau projet
                        </a>
                        <a href="{{ route('cms.admin.albums.create') }}" class="btn btn-outline-info btn-sm text-start">
                            <i class="fas fa-images me-2"></i> Nouvel album
                        </a>
                        <a href="{{ route('cms.admin.banners.create') }}" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-image me-2"></i> Nouvelle bannière
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Pages -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-file-alt text-primary me-2"></i>Pages Récentes</h5>
                </div>
                <div class="card-body">
                    @forelse($recentPages as $page)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 small">{{ $page->title }}</h6>
                                <small class="text-muted">
                                    @if($page->status === 'published')
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Publié</span>
                                    @else
                                        <span class="text-warning"><i class="fas fa-edit"></i> Brouillon</span>
                                    @endif
                                </small>
                            </div>
                            <a href="{{ route('cms.admin.pages.edit', $page) }}" class="btn btn-sm btn-light">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">Aucune page créée</p>
                    @endforelse
                </div>
            </div>
        </div>
        
        <!-- Upcoming Events -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-calendar text-success me-2"></i>Événements à Venir</h5>
                </div>
                <div class="card-body">
                    @forelse($upcomingEvents as $event)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-light rounded p-2 text-center" style="min-width: 50px;">
                                    <div class="fw-bold text-primary">{{ $event->start_date->format('d') }}</div>
                                    <small class="text-muted">{{ $event->start_date->format('M') }}</small>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 small">{{ $event->title }}</h6>
                                <small class="text-muted">{{ $event->location }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">Aucun événement à venir</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    <!-- Links to Sections -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="fas fa-link me-2"></i>Sections du Site</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('cms.admin.banners') }}" class="card border bg-light text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-image fa-2x text-secondary mb-2"></i>
                                    <h6 class="mb-0">Bannières</h6>
                                    <small class="text-muted">{{ $stats['banners'] }} actives</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('cms.admin.settings') }}" class="card border bg-light text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-cog fa-2x text-secondary mb-2"></i>
                                    <h6 class="mb-0">Paramètres</h6>
                                    <small class="text-muted">Configuration</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('frontend.home') }}" target="_blank" class="card border bg-light text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-globe fa-2x text-secondary mb-2"></i>
                                    <h6 class="mb-0">Voir le Site</h6>
                                    <small class="text-muted">Frontend</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.dashboard') }}" class="card border bg-light text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-tachometer-alt fa-2x text-secondary mb-2"></i>
                                    <h6 class="mb-0">Dashboard</h6>
                                    <small class="text-muted">Administration</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-sm {
        width: 48px;
        height: 48px;
    }
</style>
@endsection

