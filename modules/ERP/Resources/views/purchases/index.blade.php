@extends('layouts.admin-master')

@section('title', 'ERP - Gestion des Achats')
@section('page-title', 'Gestion des Achats')
@section('page-subtitle', 'Commandes fournisseurs et achats')

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-shopping-cart text-racine-orange me-2"></i>
            Gestion des Achats
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Commandes fournisseurs et achats
        </p>
    </div>
    <a href="{{ route('erp.purchases.create') }}" class="btn btn-racine-orange">
        <i class="fas fa-plus me-2"></i>
        Nouvelle Commande
    </a>
</div>

{{-- Barre de filtres --}}
@include('partials.admin.filter-bar', [
    'route' => route('erp.purchases.index'),
    'search' => true,
    'filters' => [
        [
            'name' => 'status',
            'label' => 'Statut',
            'type' => 'select',
            'icon' => 'fas fa-filter',
            'width' => 3,
            'options' => [
                ['value' => '', 'label' => 'Tous les statuts'],
                ['value' => 'ordered', 'label' => 'Commandé'],
                ['value' => 'received', 'label' => 'Reçu'],
                ['value' => 'cancelled', 'label' => 'Annulé']
            ]
        ]
    ]
])

{{-- Tableau des achats --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-hashtag me-2"></i>Référence
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-truck me-2"></i>Fournisseur
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-calendar me-2"></i>Date
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-money-bill-wave me-2"></i>Montant Total
                        </th>
                        <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-2"></i>Statut
                        </th>
                        <th class="text-end text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr>
                        <td style="padding: 1.25rem 1rem;">
                            <code class="text-racine-orange fw-bold">{{ $purchase->reference }}</code>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="fw-semibold text-racine-black">{{ $purchase->supplier->name }}</div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <div class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                {{ $purchase->purchase_date->format('d/m/Y') }}
                            </div>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            <span class="fw-bold text-racine-orange">
                                {{ number_format($purchase->total_amount, 0, ',', ' ') }} FCFA
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1rem;">
                            @php
                                $statusConfig = [
                                    'ordered' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock', 'label' => 'Commandé'],
                                    'received' => ['class' => 'bg-success text-white', 'icon' => 'fa-check-circle', 'label' => 'Reçu'],
                                    'cancelled' => ['class' => 'bg-danger text-white', 'icon' => 'fa-times-circle', 'label' => 'Annulé'],
                                ];
                                $status = $statusConfig[$purchase->status] ?? $statusConfig['ordered'];
                            @endphp
                            <span class="badge {{ $status['class'] }} rounded-pill">
                                <i class="fas {{ $status['icon'] }} me-1"></i>
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td class="text-end" style="padding: 1.25rem 1rem;">
                            <div class="btn-group" role="group">
                                <a href="{{ route('erp.purchases.show', $purchase) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Voir les détails">
                                    <i class="fas fa-eye"></i>
                                    <span class="d-none d-md-inline ms-1">Voir</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3 opacity-50"></i>
                                <p class="text-muted mb-2">Aucune commande trouvée</p>
                                <a href="{{ route('erp.purchases.create') }}" class="btn btn-racine-orange">
                                    <i class="fas fa-plus me-2"></i>
                                    Créer votre première commande
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($purchases->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $purchases->firstItem() ?? 0 }} à {{ $purchases->lastItem() ?? 0 }} sur {{ $purchases->total() }} résultats
                </div>
                <div>
                    {{ $purchases->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

