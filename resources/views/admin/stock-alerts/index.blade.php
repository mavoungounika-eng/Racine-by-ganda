@extends('layouts.admin-master')

@section('title', 'Alertes de Stock')
@section('page-title', 'Alertes de Stock')
@section('page-subtitle', 'Surveillez les niveaux de stock de vos produits')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
            Alertes de Stock
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Surveillez les niveaux de stock de vos produits
        </p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.stock-alerts.resolve-all') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check-double me-2"></i>
                Résoudre toutes les alertes
            </button>
        </form>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Retour aux produits
        </a>
    </div>
</div>

{{-- Statistiques --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Actives',
            'value' => $stats['active'],
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'warning'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Résolues',
            'value' => $stats['resolved'],
            'icon' => 'fas fa-check-circle',
            'color' => 'success'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Ignorées',
            'value' => $stats['dismissed'],
            'icon' => 'fas fa-ban',
            'color' => 'secondary'
        ])
    </div>
    
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Total',
            'value' => $stats['total'],
            'icon' => 'fas fa-list',
            'color' => 'info'
        ])
    </div>
</div>

{{-- Barre de filtres --}}
<div class="card card-racine mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.stock-alerts.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label small text-muted mb-1">
                    <i class="fas fa-search me-1"></i> Recherche
                </label>
                <input type="text" 
                       name="search" 
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Nom du produit..." 
                       class="form-control form-control-lg">
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label small text-muted mb-1">
                    <i class="fas fa-filter me-1"></i> Statut
                </label>
                <select name="status" id="status" class="form-select form-select-lg">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolues</option>
                    <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>Ignorées</option>
                </select>
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-racine-orange btn-lg w-100">
                    <i class="fas fa-filter me-2"></i>
                    Filtrer
                </button>
            </div>

            @if(request()->hasAny(['search', 'status']))
                <div class="col-md-auto">
                    <a href="{{ route('admin.stock-alerts.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                        <i class="fas fa-redo me-2"></i>
                        Réinitialiser
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Liste des alertes --}}
<div class="card card-racine">
    <div class="card-body p-0">
        @if($alerts->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-box me-2"></i>Produit
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-warehouse me-2"></i>Stock actuel
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-exclamation-circle me-2"></i>Seuil
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-toggle-on me-2"></i>Statut
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-calendar me-2"></i>Date
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">
                                {{ $alert->product->title ?? 'Produit supprimé' }}
                            </div>
                            @if($alert->product)
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-hashtag me-1"></i>
                                    ID: {{ $alert->product->id }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($alert->current_stock <= 0)
                                <span class="badge bg-danger rounded-pill">
                                    <i class="fas fa-times-circle me-1"></i>
                                    {{ $alert->current_stock }} unités
                                </span>
                            @else
                                <span class="badge bg-warning rounded-pill">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $alert->current_stock }} unités
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="text-racine-black fw-semibold">
                                <i class="fas fa-gauge-high me-1"></i>
                                {{ $alert->threshold }} unités
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @if($alert->status === 'active')
                                <span class="badge bg-warning rounded-pill">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Active
                                </span>
                            @elseif($alert->status === 'resolved')
                                <span class="badge bg-success rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i>Résolue
                                </span>
                            @else
                                <span class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-ban me-1"></i>Ignorée
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="text-racine-black small">
                                <i class="fas fa-clock me-1"></i>
                                {{ $alert->created_at->format('d/m/Y H:i') }}
                            </div>
                            @if($alert->resolved_at)
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-check me-1"></i>
                                    Résolue: {{ $alert->resolved_at->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            @if($alert->status === 'active')
                            <div class="btn-group" role="group">
                                <form action="{{ route('admin.stock-alerts.resolve', $alert) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm btn-success"
                                            title="Résoudre l'alerte"
                                            onclick="return confirm('Êtes-vous sûr de vouloir résoudre cette alerte ?')">
                                        <i class="fas fa-check"></i>
                                        <span class="d-none d-md-inline ms-1">Résoudre</span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.stock-alerts.dismiss', $alert) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Ignorer l'alerte"
                                            onclick="return confirm('Êtes-vous sûr de vouloir ignorer cette alerte ?')">
                                        <i class="fas fa-times"></i>
                                        <span class="d-none d-md-inline ms-1">Ignorer</span>
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-muted">
                                <i class="fas fa-minus"></i>
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($alerts->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $alerts->firstItem() ?? 0 }} à {{ $alerts->lastItem() ?? 0 }} sur {{ $alerts->total() }} résultats
                </div>
                <div>
                    {{ $alerts->links() }}
                </div>
            </div>
        </div>
        @endif
        @else
        <div class="text-center py-5">
            <div class="py-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-check-circle fa-3x text-success"></i>
                </div>
                <h4 class="fw-bold text-racine-black mb-2">Aucune alerte</h4>
                <p class="text-muted mb-3">Tous les stocks sont suffisants.</p>
                <a href="{{ route('admin.products.index') }}" class="btn btn-racine-orange">
                    <i class="fas fa-box me-2"></i>
                    Voir les produits
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

