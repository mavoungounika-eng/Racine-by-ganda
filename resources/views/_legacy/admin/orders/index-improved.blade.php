@extends('layouts.admin')

@section('title', 'Gestion des Commandes')
@section('page-title', 'Gestion des Commandes')
@section('page-subtitle', 'Gérer toutes les commandes de la plateforme')

@section('content')

{{-- En-tête --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-shopping-cart text-racine-orange me-2"></i>
            Gestion des Commandes
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            {{ $orders->total() }} commande(s) au total
        </p>
    </div>
</div>

{{-- Barre de filtres --}}
@include('partials.admin.filter-bar', [
    'route' => route('admin.orders.index'),
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
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'paid', 'label' => 'Payée'],
                ['value' => 'shipped', 'label' => 'Expédiée'],
                ['value' => 'completed', 'label' => 'Terminée'],
                ['value' => 'cancelled', 'label' => 'Annulée']
            ]
        ]
    ]
])

{{-- Tableau des commandes --}}
<div class="card card-racine">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">
                            <i class="fas fa-hashtag me-2"></i>ID
                        </th>
                        <th>
                            <i class="fas fa-user me-2"></i>Client
                        </th>
                        <th>
                            <i class="fas fa-money-bill-wave me-2"></i>Total
                        </th>
                        <th>
                            <i class="fas fa-info-circle me-2"></i>Statut
                        </th>
                        <th>
                            <i class="fas fa-calendar me-2"></i>Date
                        </th>
                        <th class="text-end" style="width: 120px;">
                            <i class="fas fa-cog me-2"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <span class="fw-bold text-racine-black">
                                #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-racine-black">
                                {{ $order->customer_name ?? $order->user?->name ?? 'N/A' }}
                            </div>
                            @if($order->customer_email ?? $order->user?->email)
                                <div class="small text-muted">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ $order->customer_email ?? $order->user->email }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-racine-orange">
                                {{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA
                            </span>
                        </td>
                        <td>
                            @php
                                $statusConfig = [
                                    'pending' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock', 'label' => 'En attente'],
                                    'paid' => ['class' => 'bg-info text-white', 'icon' => 'fa-check-circle', 'label' => 'Payée'],
                                    'shipped' => ['class' => 'bg-primary text-white', 'icon' => 'fa-shipping-fast', 'label' => 'Expédiée'],
                                    'completed' => ['class' => 'bg-success text-white', 'icon' => 'fa-check-double', 'label' => 'Terminée'],
                                    'cancelled' => ['class' => 'bg-danger text-white', 'icon' => 'fa-times-circle', 'label' => 'Annulée'],
                                ];
                                $status = $statusConfig[$order->status] ?? $statusConfig['pending'];
                            @endphp
                            <span class="badge {{ $status['class'] }} rounded-pill">
                                <i class="fas {{ $status['icon'] }} me-1"></i>
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td>
                            <div class="text-muted small">
                                <i class="fas fa-calendar-alt me-1"></i>
                                {{ $order->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                {{ $order->created_at->format('H:i') }}
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.orders.show', $order) }}" 
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
                                <p class="text-muted small">Les commandes apparaîtront ici une fois créées.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($orders->hasPages())
        <div class="card-footer bg-transparent border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $orders->firstItem() ?? 0 }} à {{ $orders->lastItem() ?? 0 }} sur {{ $orders->total() }} résultats
                </div>
                <div>
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

