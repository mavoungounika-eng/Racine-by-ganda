@extends('layouts.admin-master')

@section('title', 'Rapport de Validation - Créateurs')
@section('page-title', 'Rapport de Validation')
@section('page-subtitle', 'Statistiques de validation des créateurs')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-chart-bar text-racine-orange me-2"></i>
            Rapport de Validation
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Statistiques de validation des créateurs
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.creators.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Retour à la liste
        </a>
        <a href="{{ route('admin.creators.export.csv') }}" class="btn btn-racine-orange">
            <i class="fas fa-download me-2"></i>
            Exporter CSV
        </a>
    </div>
</div>

{{-- Statistiques principales --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Total Créateurs',
            'value' => $stats['total'],
            'icon' => 'fas fa-users',
            'color' => 'primary'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'En attente',
            'value' => $stats['pending'],
            'icon' => 'fas fa-clock',
            'color' => 'warning'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Actifs',
            'value' => $stats['active'],
            'icon' => 'fas fa-check-circle',
            'color' => 'success'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Vérifiés',
            'value' => $stats['verified'],
            'icon' => 'fas fa-check',
            'color' => 'info'
        ])
    </div>
</div>

{{-- Détails --}}
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-pie text-racine-orange me-2"></i>
                    Répartition par statut
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>En attente</span>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="width: 200px; height: 20px;">
                                <div class="progress-bar bg-warning" 
                                     style="width: {{ $stats['total'] > 0 ? ($stats['pending'] / $stats['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                            <strong>{{ $stats['pending'] }}</strong>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Actifs</span>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="width: 200px; height: 20px;">
                                <div class="progress-bar bg-success" 
                                     style="width: {{ $stats['total'] > 0 ? ($stats['active'] / $stats['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                            <strong>{{ $stats['active'] }}</strong>
                        </div>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Suspendus</span>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="width: 200px; height: 20px;">
                                <div class="progress-bar bg-danger" 
                                     style="width: {{ $stats['total'] > 0 ? ($stats['suspended'] / $stats['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                            <strong>{{ $stats['suspended'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-file-alt text-racine-orange me-2"></i>
                    Documents
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Avec documents</span>
                        <strong class="text-success">{{ $stats['with_documents'] }}</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Sans documents</span>
                        <strong class="text-danger">{{ $stats['without_documents'] }}</strong>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Taux de complétion moyen</span>
                        <strong class="text-racine-orange">{{ number_format($stats['avg_completion'], 1) }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Vérification --}}
<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-check-circle text-racine-orange me-2"></i>
                    Vérification
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Vérifiés</span>
                        <strong class="text-success">{{ $stats['verified'] }}</strong>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Non vérifiés</span>
                        <strong class="text-warning">{{ $stats['unverified'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

