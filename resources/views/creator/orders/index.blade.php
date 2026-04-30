@extends('layouts.creator')

@section('title', 'Mes Commandes - RACINE BY GANDA')
@section('page-title', 'Mes Commandes')


@section('content')
<div class="container-fluid py-4">
    {{-- Stats rapides --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-md">
            <div class="creator-stat-card-premium" style="--stat-color-1: #ED5F1E; --stat-color-2: #FFB800;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="creator-stat-label">Total</p>
                        <p class="creator-stat-value" style="color: #ED5F1E;">{{ $stats['total'] }}</p>
                    </div>
                    <div class="creator-stat-icon" style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);">
                        <i class="fas fa-shopping-cart text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="creator-stat-card-premium" style="--stat-color-1: #FFB800; --stat-color-2: #ED5F1E;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="creator-stat-label">En attente</p>
                        <p class="creator-stat-value" style="color: #FFB800;">{{ $stats['pending'] }}</p>
                    </div>
                    <div class="creator-stat-icon" style="background: linear-gradient(135deg, #FFB800 0%, #ED5F1E 100%);">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="creator-stat-card-premium">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="creator-stat-label">Payées</p>
                        <p class="creator-stat-value creator-stat-value--paid">{{ $stats['paid'] }}</p>
                    </div>
                    <div class="creator-stat-icon creator-stat-icon--paid">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="creator-stat-card-premium">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="creator-stat-label">Expédiées</p>
                        <p class="creator-stat-value creator-stat-value--shipped">{{ $stats['shipped'] }}</p>
                    </div>
                    <div class="creator-stat-icon creator-stat-icon--shipped">
                        <i class="fas fa-shipping-fast text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="creator-stat-card-premium">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="creator-stat-label">Terminées</p>
                        <p class="creator-stat-value creator-stat-value--done">{{ $stats['completed'] }}</p>
                    </div>
                    <div class="creator-stat-icon creator-stat-icon--done">
                        <i class="fas fa-check-double text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="creator-card mb-4">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <label class="creator-form-label mb-0">Filtrer par statut :</label>
            <select name="status" class="creator-select">
                <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Tous les statuts</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payées</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Expédiées</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminées</option>
            </select>

            <button type="submit" class="creator-btn">
                <i class="fas fa-filter"></i>
                Filtrer
            </button>

            @if(request()->has('status'))
                <a href="{{ route('creator.orders.index') }}" class="creator-btn creator-btn--muted">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Liste des commandes --}}
    <div class="creator-card overflow-hidden">
        <div class="table-responsive">
            <table class="creator-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <p class="creator-table-id">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </td>
                        <td>
                            <p class="creator-table-muted fw-medium">{{ $order->created_at->format('d/m/Y') }}</p>
                            <p class="creator-table-muted small">{{ $order->created_at->format('H:i') }}</p>
                        </td>
                        <td>
                            <p class="creator-table-name">{{ $order->customer_name ?? $order->user?->name ?? 'N/A' }}</p>
                            <p class="creator-table-muted small">{{ $order->customer_email ?? $order->user?->email ?? '' }}</p>
                        </td>
                        <td>
                            <p class="creator-table-amount">{{ number_format($order->creator_total ?? 0, 0, ',', ' ') }} F</p>
                            <p class="creator-table-muted small">Vos produits</p>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending'        => ['bg' => 'rgba(234,179,8,0.1)',   'text' => '#D97706', 'border' => 'rgba(234,179,8,0.25)',   'icon' => 'fa-clock'],
                                    'paid'           => ['bg' => 'rgba(59,130,246,0.1)',  'text' => '#2563EB', 'border' => 'rgba(59,130,246,0.25)',  'icon' => 'fa-check-circle'],
                                    'in_production'  => ['bg' => 'rgba(139,92,246,0.1)',  'text' => '#7C3AED', 'border' => 'rgba(139,92,246,0.25)',  'icon' => 'fa-cog'],
                                    'ready_to_ship'  => ['bg' => 'rgba(99,102,241,0.1)',  'text' => '#4F46E5', 'border' => 'rgba(99,102,241,0.25)',  'icon' => 'fa-box'],
                                    'shipped'        => ['bg' => 'rgba(139,92,246,0.1)',  'text' => '#7C3AED', 'border' => 'rgba(139,92,246,0.25)',  'icon' => 'fa-shipping-fast'],
                                    'completed'      => ['bg' => 'rgba(22,160,133,0.1)',  'text' => '#0D9E88', 'border' => 'rgba(22,160,133,0.25)',  'icon' => 'fa-check-double'],
                                    'cancelled'      => ['bg' => 'rgba(239,68,68,0.1)',   'text' => '#DC2626', 'border' => 'rgba(239,68,68,0.25)',   'icon' => 'fa-times-circle'],
                                ];
                                $status = $statusColors[$order->status] ?? $statusColors['pending'];
                            @endphp
                            <span class="creator-badge" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }}; border: 1px solid {{ $status['border'] }};">
                                <i class="fas {{ $status['icon'] }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <a href="{{ route('creator.orders.show', $order) }}"
                                   class="creator-action-btn creator-action-btn--view"
                                   title="Voir les détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-5 text-center">
                            <div class="creator-empty-state">
                                <i class="fas fa-shopping-cart creator-empty-state__icon"></i>
                                <p class="creator-empty-state__title">Aucune commande trouvée</p>
                                <p class="creator-empty-state__text">Vos commandes apparaîtront ici</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="creator-table-pagination">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
